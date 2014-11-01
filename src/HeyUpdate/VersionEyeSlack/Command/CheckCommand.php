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
        $this->addOption('slack-channel', null, InputOption::VALUE_REQUIRED, 'The Slack channel to post to');
        $this->addOption('versioneye-key', null, InputOption::VALUE_REQUIRED, 'The VersionEye API key');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->loadConfig();

        $client = new Client();
        $response = $client->get('https://www.versioneye.com/api/v2/me/notifications', [
            'query' => [
                'api_key' => $input->getOption('versioneye-key')
            ]
        ]);

        if ($response->getStatusCode() !== '200') {
            throw new \RuntimeException('Unable to fetch notifications from VersionEye');
        }

        $result = $response->json();

        // Build slack attachments
        $attachments = [];

        if (isset($result['notifications']) && count($result['notifications']) > 0) {
            $lastNotificationDate = isset($config['last_notificiation']) ? new \DateTime($config['last_notificiation']) : null;
            foreach ($result['notifications'] as $notification) {
                $notificiationDate = new \DateTime((string) $notification['created_at']);
                if (!isset($lastNotificationDate) || $lastNotificationDate < $notificiationDate) {
                    // Build the URL for the product on VersionEye (for more info)
                    $url = sprintf('https://www.versioneye.com/%s/%s',
                        strtolower($notification['product']['language']),
                        strtolower(str_replace('/', ':', $notification['product']['prod_key']))
                    );

                    $attachments[] = [
                        'text' => sprintf('<%s|%s (%s)>', $url, $notification['product']['name'], $notification['version'])
                    ];
                }
            }
        }

        if (count($attachments) > 0) {
            $response = $client->post($input->getOption('slack-webhook'), [
                'body' => [
                    'payload' => json_encode([
                        // Default to the #general channel
                        'channel' => $input->getOption('slack-channel') ?: '#general',
                        'username' => 'VersionEye',
                        'icon_url' => 'https://raw.githubusercontent.com/heyupdate/versioneye-slack/gh-pages/versioneye.png',
                        'text' => 'There are new releases out there!',
                        'attachments' => $attachments
                    ])
                ]
            ]);

            if ($response->getStatusCode() !== '200') {
                throw new \RuntimeException('Unable to post new releases to Slack');
            }
        }

        // Set the last notification date so we don't post it twice
        $config['last_notificiation'] = date('c');

        $this->saveConfig($config);
    }

    protected function getConfigFile()
    {
        return $_SERVER['HOME'] . '/.versioneye-slack.json';
    }

    private function loadConfig()
    {
        $config = [];
        $configFile = $this->getConfigFile();

        if (is_file($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
        }

        return $config;
    }

    private function saveConfig(array $config)
    {
        file_put_contents($this->getConfigFile(), json_encode($config));
    }
}
