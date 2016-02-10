# Prerequisites

* Update `src/Resources/config.yml`
    * Set your Github user name
    * Generate a Github API token or run `composer config --global --list` and use the token from `github-oauth.github.com` field.
    * Update the repos list mapping for the repos you need. PRs are welcome.
    * Install the dependencies by running `composer install`
    
# Running the app

* `php app.php build <repoName> <brachName>` to build the repo and start the deployment process
* `php app.php build` to take advantage of auto-complete features.
* `php app.php deploy <repoName> <buildNumber>` to deploy a specific build.

See `php app.php` for more details on the usage.

# Quirks
It's worth noting that, *by default*, the app creates a PR instead of direct commits to master. Change this behaviour at our own risk.

# Known drawbacks
* Currently only building test environment is supported
