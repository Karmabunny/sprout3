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

