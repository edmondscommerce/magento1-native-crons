# magento1-native-crons

System for running Magento 1 crons natively.

## By [Edmonds Commerce](https://www.edmondscommerce.co.uk)

### Installation

- Place the generate_cron_shell_scripts.php inside your Magento 1 project shell folder and execute it by running `php generate_cron_shell_scripts.php`
- After cronrunner directory gets generated place cronRunner.php inside it.
- Load crontab.txt into your by typing `crontab path/to/file`
- Enjoy having more control over your Magento cron jobs.