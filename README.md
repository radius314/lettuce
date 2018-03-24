#lettuce

1. To get this going, stand up the database docker container.

`docker-compose up`

2. Take a mysql dump from your source and target mysql dbs.

3. Import the two dumps as two separate DBs on the docker container.

4. Take a snapshot of the container, in case you need to restart this.

`docker commit [container-id] [image:tag]`

5. Fill in the variables.

6. Run it.