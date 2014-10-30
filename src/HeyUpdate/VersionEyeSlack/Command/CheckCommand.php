<?php

namespace HeyUpdate\VersionEyeSlack\Command;

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCommand extends Command
{
    protected function configure()
    {
        $this->setName('check');

        $this->addOption('slack-webhook', null, InputOption::VALUE_REQUIRED, 'The Slack incoming webhook URL');
        $this->addOption('versioneye-key', null, InputOption::VALUE_REQUIRED, 'The VersionEye API key');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configFile = $_SERVER['HOME'] . '/.versioneye-slack.json';
        if (is_file($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
        } else {
            $config = [];
        }

        $client = new Client();

        $response = $client->get('https://www.versioneye.com/api/v2/me/notifications', [
            'query' => [
                'api_key' => $input->getOption('versioneye-key')
            ]
        ]);

        $result = json_decode((string) $response->getBody());

        // Build slack attachments
        $attachments = array();
        $lastNotificationDate = new \DateTime($config['last_notificiation']);
        foreach ($result->notifications as $notification) {
            $notificiationDate = new \DateTime((string) $notification->created_at);
            if (!isset($config['last_notificiation']) || $lastNotificationDate < $notificiationDate) {
                $attachments[] = [
                    'text' => sprintf('%s (%s)', $notification->product->name, $notification->version)
                ];
            }
        }

        if (count($attachments) > 0) {
            $response = $client->post($input->getOption('slack-webhook'), [
                'body' => [
                    'payload' => json_encode([
                        'channel' => '#general',
                        'username' => 'VersionEye',
                        'icon_url' => 'https://pbs.twimg.com/profile_images/476315273247485952/ZnbAxqnh.png',
                        'text' => 'There are new releases out there!',
                        'attachments' => $attachments
                    ])
                ]
            ]);
        }

        $config['last_notificiation'] = date('c');

        file_put_contents($configFile, json_encode($config));
    }
}
