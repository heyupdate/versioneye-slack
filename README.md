# VersionEye Slack integration

Post your latest VersionEye notifications to Slack.

![Screenshot](https://raw.githubusercontent.com/heyupdate/versioneye-slack/gh-pages/screenshot.png)

## Installation

There are two ways to install; using Composer or downloading a Phar binary.

### Using Composer

Install globally with [Composer](https://getcomposer.org/doc/03-cli.md#global):

    composer global require 'heyupdate/versioneye-slack=~0.1'

To update you can then use:

    composer global update

Be sure to add `~/.composer/vendor/bin` to your `$PATH`.

### Download the Phar binary

Download the `sqwack.phar` binary from the latest release.

https://github.com/heyupdate/versioneye-slack/releases/latest

Make the file executable

    chmod +x ~/Downloads/versioneye-slack.phar

Run it

    ~/Downloads/versioneye-slack.phar

## Check for notifications (and post them to a Slack channel)

Get your VersionEye API key, which you can find at:

    https://www.versioneye.com/settings/api

Add a new *Incoming Web Hook* integration on Slack and copy your *Web Hook URL*.

You can post to any channel by passing it as an option (it will default to `#general`).

    versioneye-slack --versioneye-key={key} --slack-webhook={webhook} --slack-channel=#general

## Help

Get help by running:

    versioneye-slack --help
