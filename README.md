#lettuce

###Important Information

1. Think about your desired service body tree.  If collapsing into a zone, make those changes ahead of time on the target.
2. Always take backups.
3. There might some additional actions to take of, look at the console messages.
4. If you have some custom fields, you will need to add them in the target database first before exporting the dump.

###How to use Lettuce

1. To get this going, stand up the database docker container and sample BMLT server for testing by running:

`docker-compose up`

2. Take a mysql dump from your source and target mysql dbs.

3. Import the two dumps as two separate DBs on the docker container.

4. Take a snapshot of the container, in case you need to restart this.

`docker commit [container-id] [image:tag]`

5. Fill in the variables in `functions.php`.

6. Run `php bootstrap.php`, this will generate clean.php which will run each time you re-run `run.php`, in case you need to start over.

7. Run `php run.php`.