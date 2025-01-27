# Shlink Docker image

[![Docker build status](https://img.shields.io/docker/cloud/build/shlinkio/shlink.svg?style=flat-square)](https://hub.docker.com/r/shlinkio/shlink/)
[![Docker pulls](https://img.shields.io/docker/pulls/shlinkio/shlink.svg?style=flat-square)](https://hub.docker.com/r/shlinkio/shlink/)

This image provides an easy way to set up [shlink](https://shlink.io) on a container-based runtime.

It exposes a shlink instance served with [swoole](https://www.swoole.co.uk/), which persists data in a local [sqlite](https://www.sqlite.org/index.html) database.

## Usage

Shlink docker image exposes port `8080` in order to interact with its HTTP interface.

It also expects these two env vars to be provided, in order to properly generate short URLs at runtime.

* `SHORT_DOMAIN_HOST`: The custom short domain used for this shlink instance. For example **doma.in**.
* `SHORT_DOMAIN_SCHEMA`: Either **http** or **https**.

So based on this, to run shlink on a local docker service, you should run a command like this:

```bash
docker run --name shlink -p 8080:8080 -e SHORT_DOMAIN_HOST=doma.in -e SHORT_DOMAIN_SCHEMA=https shlinkio/shlink:stable
```

### Interact with shlink's CLI on a running container.

Once the shlink container is running, you can interact with the CLI tool by running `shlink` with any of the supported commands.

For example, if the container is called `shlink_container`, you can generate a new API key with:

```bash
docker exec -it shlink_container shlink api-key:generate
```

Or you can list all tags with:

```bash
docker exec -it shlink_container shlink tag:list
```

Or process remaining visits with:

```bash
docker exec -it shlink_container shlink visit:process
```

All shlink commands will work the same way.

You can also list all available commands just by running this:

```bash
docker exec -it shlink_container shlink
```

## Use an external DB

The image comes with a working sqlite database, but in production you will probably want to usa a distributed database.

It is possible to use a set of env vars to make this shlink instance interact with an external MySQL, MariaDB or PostgreSQL database.

* `DB_DRIVER`: **[Mandatory]**. Use the value **mysql**, **maria** or **postgres** to prevent the sqlite database to be used.
* `DB_NAME`: [Optional]. The database name to be used. Defaults to **shlink**.
* `DB_USER`: **[Mandatory]**. The username credential for the database server.
* `DB_PASSWORD`: **[Mandatory]**. The password credential for the database server.
* `DB_HOST`: **[Mandatory]**. The host name of the server running the database engine.
* `DB_PORT`: [Optional]. The port in which the database service is running.
    * Default value is based on the value provided for `DB_DRIVER`:
        * **mysql** or **maria** -> `3306`
        * **postgres** -> `5432`

> PostgreSQL is supported since v1.16.1 of this image. Do not try to use it with previous versions.

Taking this into account, you could run shlink on a local docker service like this:

```bash
docker run --name shlink -p 8080:8080 -e SHORT_DOMAIN_HOST=doma.in -e SHORT_DOMAIN_SCHEMA=https -e DB_DRIVER=mysql -e DB_USER=root -e DB_PASSWORD=123abc -e DB_HOST=something.rds.amazonaws.com shlinkio/shlink:stable
```

You could even link to a local database running on a different container:

```bash
docker run --name shlink -p 8080:8080 [...] -e DB_HOST=some_mysql_container --link some_mysql_container shlinkio/shlink:stable
```

> If you have considered using SQLite but sharing the database file with a volume, read [this issue](https://github.com/shlinkio/shlink-docker-image/issues/40) first.

## Supported env vars

A few env vars have been already used in previous examples, but this image supports others that can be used to customize its behavior.

This is the complete list of supported env vars:

* `SHORT_DOMAIN_HOST`: The custom short domain used for this shlink instance. For example **doma.in**.
* `SHORT_DOMAIN_SCHEMA`: Either **http** or **https**.
* `DB_DRIVER`: **sqlite** (which is the default value), **mysql**, **maria** or **postgres**.
* `DB_NAME`: The database name to be used when using an external database driver. Defaults to **shlink**.
* `DB_USER`: The username credential to be used when using an external database driver.
* `DB_PASSWORD`: The password credential to be used when using an external database driver.
* `DB_HOST`: The host name of the database server  when using an external database driver.
* `DB_PORT`: The port in which the database service is running when using an external database driver.
    * Default value is based on the value provided for `DB_DRIVER`:
        * **mysql** or **maria** -> `3306`
        * **postgres** -> `5432`
* `DISABLE_TRACK_PARAM`: The name of a query param that can be used to visit short URLs avoiding the visit to be tracked. This feature won't be available if not value is provided.
* `DELETE_SHORT_URL_THRESHOLD`: The amount of visits on short URLs which will not allow them to be deleted. Defaults to `15`.
* `VALIDATE_URLS`: Boolean which tells if shlink should validate a status 20x (after following redirects) is returned when trying to shorten a URL. Defaults to `true`.
* `INVALID_SHORT_URL_REDIRECT_TO`: If a URL is provided here, when a user tries to access an invalid short URL, he/she will be redirected to this value. If this env var is not provided, the user will see a generic `404 - not found` page.
* `REGULAR_404_REDIRECT_TO`: If a URL is provided here, when a user tries to access a URL not matching any one supported by the router, he/she will be redirected to this value. If this env var is not provided, the user will see a generic `404 - not found` page.
* `BASE_URL_REDIRECT_TO`: If a URL is provided here, when a user tries to access Shlink's base URL, he/she will be redirected to this value. If this env var is not provided, the user will see a generic `404 - not found` page.
* `BASE_PATH`: The base path from which you plan to serve shlink, in case you don't want to serve it from the root of the domain. Defaults to `''`.
* `WEB_WORKER_NUM`: The amount of concurrent http requests this shlink instance will be able to server. Defaults to 16.
* `TASK_WORKER_NUM`: The amount of concurrent background tasks this shlink instance will be able to execute. Defaults to 16.
* `REDIS_SERVERS`: A comma-separated list of redis servers where Shlink locks are stored (locks are used to prevent some operations to be run more than once in parallel).

    This is important when running more than one Shlink instance ([Multi instance considerations](#multi-instance-considerations)). If not provided, Shlink stores locks on every instance separately.

    If more than one server is provided, Shlink will expect them to be configured as a [redis cluster](https://redis.io/topics/cluster-tutorial).

    In the future, these redis servers could be used for other caching operations performed by shlink.

* `NOT_FOUND_REDIRECT_TO`: **Deprecated since v1.20 in favor of `INVALID_SHORT_URL_REDIRECT_TO`** If a URL is provided here, when a user tries to access an invalid short URL, he/she will be redirected to this value. If this env var is not provided, the user will see a generic `404 - not found` page.
* `SHORTCODE_CHARS`: **Ignored when using Shlink 1.20 or newer**. A charset to use when building short codes. Only needed when using more than one shlink instance ([Multi instance considerations](#multi-instance-considerations)).

An example using all env vars could look like this:

```bash
docker run \
    --name shlink \
    -p 8080:8080 \
    -e SHORT_DOMAIN_HOST=doma.in \
    -e SHORT_DOMAIN_SCHEMA=https \
    -e DB_DRIVER=mysql \
    -e DB_NAME=shlink \
    -e DB_USER=root \
    -e DB_PASSWORD=123abc \
    -e DB_HOST=something.rds.amazonaws.com \
    -e DB_PORT=3306 \
    -e DISABLE_TRACK_PARAM="no-track" \
    -e DELETE_SHORT_URL_THRESHOLD=30 \
    -e VALIDATE_URLS=false \
    -e "INVALID_SHORT_URL_REDIRECT_TO=https://my-landing-page.com" \
    -e "REGULAR_404_REDIRECT_TO=https://my-landing-page.com" \
    -e "BASE_URL_REDIRECT_TO=https://my-landing-page.com" \
    -e "REDIS_SERVERS=tcp://172.20.0.1:6379,tcp://172.20.0.2:6379" \
    -e "BASE_PATH=/my-campaign" \
    -e WEB_WORKER_NUM=64 \
    -e TASK_WORKER_NUM=32 \
    shlinkio/shlink:stable
```

## Provide config via volumes

Rather than providing custom configuration via env vars, it is also possible ot provide config files in json format.

Mounting a volume at `config/params` you will make shlink load all the files on it with the `.config.json` suffix.

The whole configuration should have this format, but it can be split into multiple files that will be merged:

```json
{
    "disable_track_param": "my_param",
    "delete_short_url_threshold": 30,
    "short_domain_schema": "https",
    "short_domain_host": "doma.in",
    "validate_url": false,
    "invalid_short_url_redirect_to": "https://my-landing-page.com",
    "regular_404_redirect_to": "https://my-landing-page.com",
    "base_url_redirect_to": "https://my-landing-page.com",
    "base_path": "/my-campaign",
    "web_worker_num": 64,
    "task_worker_num": 32,
    "redis_servers": [
        "tcp://172.20.0.1:6379",
        "tcp://172.20.0.2:6379"
    ],
    "db_config": {
        "driver": "pdo_mysql",
        "dbname": "shlink",
        "user": "root",
        "password": "123abc",
        "host": "something.rds.amazonaws.com",
        "port": "3306"
    },
    "not_found_redirect_to": "https://my-landing-page.com"
}
```

> This is internally parsed to how shlink expects the config. If you are using a version previous to 1.17.0, this parser is not present and you need to provide a config structure like the one [documented previously](https://github.com/shlinkio/shlink-docker-image/tree/v1.16.3#provide-config-via-volumes).

> The `not_found_redirect_to` option has been deprecated in v1.20. Use `invalid_short_url_redirect_to` instead (however, it will still work for backwards compatibility).

Once created just run shlink with the volume:

```bash
docker run --name shlink -p 8080:8080 -v ${PWD}/my/config/dir:/etc/shlink/config/params shlinkio/shlink:stable
```

## Multi instance considerations

These are some considerations to take into account when running multiple instances of shlink.

* Some operations performed by Shlink should never be run more than once at the same time (like creating the database for the first time, or downloading the GeoLite2 database). For this reason, Shlink uses a locking system.

    However, these locks are locally scoped to each Shlink instance by default.

    You can (and should) make the locks to be shared by all Shlink instances by using a redis server/cluster. Just define the `REDIS_SERVERS` env var with the list of servers.

* **Ignore this if using Shlink 1.20 or newer**. The first time shlink is run, it generates a charset used to generate short codes, which is a shuffled base62 charset.

    If you are using several shlink instances, you will probably want all of them to use the same charset.

    You can get a shuffled base62 charset by going to [https://shlink.io/short-code-chars](https://shlink.io/short-code-chars), and then you just need to pass it to all shlink instances using the `SHORTCODE_CHARS` env var.

    If you don't do this, each shlink instance will use a different charset. However this shouldn't be a problem in practice, since the chances to get a collision will be very low.

## Versions

Versioning on this docker image works as follows:

* `X.X.X`:  when providing a specific version number, the image version will match the shlink version it contains. For example, installing `shlinkio/shlink:1.15.0`, you will get an image containing shlink v1.15.0.
* `stable`: always holds the latest stable tag. For example, if latest shlink version is 1.20.0, installing `shlinkio/shlink:stable`, you will get an image containing shlink v1.20.0
* `latest`: always holds the latest contents in master, and it's considered unstable and not suitable for production.

> **Important**: The docker image was introduced with shlink v1.15.0, so there are no official images previous to that versions.
