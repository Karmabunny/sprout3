## Git deploy

On the server:

1. Install the `post-receive` hook into `.git/hooks`.

2. Configure the repo to update the working directory on push:

   `git config receive.denyCurrentBranch updateInstead`


On the dev machine:

1. Create a production remote:

   `git remote add production user@example.com:/path/to/repo`

2. Push to deploy:

   `git push production master`



### Deploy actions

The sample `post-receive` hook simply redirects into a script that is managed in the repo. This means that deploy actions can be updated without needing to re-install the hook.

If using something other than git deploy, these actions are still recommended.

```sh
# update composer dependencies
composer install --no-dev

# update database schemas
php web/index.php dbtools/sync

# clean out the media cache
php web/index.php media_tools/clean

# clean out the kohana cache
rm  -f storage/cache/kohana_*
```
