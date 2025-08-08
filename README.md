![Last release](https://github.com/juanparati/MyBackup/actions/workflows/on_release.yml/badge.svg)

# MyBackup

       __  ___     ___           __           
      /  |/  /_ __/ _ )___ _____/ /____ _____
     / /|_/ / // / _  / _ `/ __/  '_/ // / _ \
    /_/  /_/\_, /____/\_,_/\__/_/\_\\_,_/ .__/
           /___/ MySQL/MariaDB backup  /_/

## What is it?

A backup tool for MySQL/MariaDB that support the following features:

- Backup plan configuration (Copy entire databases or specific tables with custom options).
- On-fly GZ compression and decompression.
- Snapshot encryption.
- Snapshot rotation.
- Notifications (Slack and e-mail).
- Read-replica verification (Verify that your read replica is synchronized before performing a backup).
- Custom actions (Copy/Move/Remove between physical and cloud filesystems).
- Dynamic placeholders (Set snapshot names based on date, time, uuid, etc.).
- Process lock (It avoids overlapping the same backup process).
- Set configuration values from environment variables.
- Backup restoration.

## Builds

See [https://github.com/juanparati/MyBackup/releases/latest](https://github.com/juanparati/MyBackup/releases/latest)

## How does it work?

### Perform backups

1. Create a backup plan configuration:

        mybackup init backup_plan.yaml

2. Edit the backup plan configuration file.
3. Verify backup and simulate a backup process:

        mybackup backup backup_plan.yaml --dry

4. Run the backup process:
    
        mybackup backup backup_plan.yaml

## Backup plan and options

An example of a complete backup plan may look like the following one:

```YAML
name: "Backup plan"
catalog_file: "/tmp/catalog.sqlite"                   # Path to the snapshot catalog.
snapshot_file: "/tmp/snapshot_{{numeric:{{datetime}}}}.sql" # Path to the snapshot.
connection:
   driver: "mysql"                                    # Supported drivers are "mysql" and "mariadb".                                                                                       
   host: "localhost"
   port: 3306
   username: "root"
   password: "secret"                                 # When this line is missing it will prompt the password.
mysqldump_path: "mysqldump"                           # Path to mysqldump/mariadb-dump.            
gzip_path: "gzip"                                     # Path to gzip (Only used when compress != false)
backup_rotation: "2 days"                             # It will delete the backups older than 2 days.
compress: true                                        # It will compress the snapshot file (Use 1-9 for compression level).                
is_replica: true                                      # It will check if the read-replica is synchronized.                             
databases:
   - firstdb                                          # It will dump the entire "firstdb" database.
   - secondb:                                         # It will dump the "seconddb" database with custom options and ignoring some tables.
        options: [ "--single-transaction", "--quick", "--compress" ]
        ignore: [ "table1", "table2" ]
   - thirddb: # It will dump some tables of the "thirddb" database.
        options: [ "--single-transaction", "--quick" ]
        to: firstdb                                   # It will copy the tables into the "firstdb".
        tables:
           - table1:
                where: "id >= 1000000"
           - table2
           - table3:
                where: "created_at >= DATE(DATE_SUB(NOW(), INTERVAL 1 MONTH))"
encryption:                                           # It will encrypt the snapshot file.
   method: "AES-128-CBC"
   key: "mysecretkey12345"
notifications:                                        # It will send a Slack notification when backup is finished.
   slack:
      username: 'BackupBot'
      channel: "[CHANNELID]"
      webhook: "https://hooks.slack.com/services/[ID]"
filesystems:                                          # It will define different filesystems.
   gcloud:
      driver: gcs
      project_id: "my_project"
      bucket: "mybackups"
      key_file:
         type: "service_account"
         private_key_id: ""
         private_key: ""
         client_email: ""
         client_id: ""
         auth_uri: ""
         token_uri: ""
         auth_provider_x509_cert_url: ""
         client_x509_cert_url: ""
   s3:
      driver: s3
      key: ''
      secret: ''
      region: 'eu-central-1'
      bucket: 'mybackup-bucket'
post_actions:
   - copy:                                          # Copy a snapshot file to gcloud filesystem.
        filesystem: gcloud
        source: '{{snapshot_file}}'
        destination: '{{basename:{{snapshot_file}}}}'
   - copy:                                          # Copy a snapshot file to s3 filesystem.
        filesystem: s3
        source: '{{snapshot_file}}'
        destination: '{{uuid}}.sql.gz.aes'
   - delete_old:                                    # Delete all files older than 3 days which name starts with "snapshot_" into the gcloud filesystem.
        filesystem: gcloud
        pattern: 'snapshot_*'
        period: '3 days'
   - run:
        command: 'echo "Finished"'                  # Run shell command
```

### Catalog file

Defined by the option "**catalog_file**".

It's a sqlite file that contains the historical list of the previous completed backups and the lock information.
The lock information will avoid overlap two or more backup processes.

Each backup plan requires a different catalog file.

The catalog file is created automatically when it's missing.

### Snapshot file

Defined by the option "**snapshot_file**".

It's the destination of the backup snapshot. In case that file is compressed and/or encrypted, the file may have additional extensions.

### Connection

Defined by option "**connection**".

Indicate the connection options. The only supported drivers are "mysql" and "mariadb", however, MariaDB also works with "mysql" driver.

The possible options are:

```YAML
connection:
    driver: 'mysql'
    url: ''
    host: '127.0.0.1'
    port: '3306'
    database: 'laravel'
    username: 'root'
    password: ''
    unix_socket: ''
```
### MySQLDump path

Defined by the option "**mysqldump_path**".

MyBackup uses internal "mysqldump" or "mariadb-dump". If the full path to the dump tool is not supplied, then MyBackup will attempt to find the path automatically.

Example:

```YAML
mysqldump_path: "mariadb-dump"    # It will attempt to find the path to mariadb-dump
```

### Backup rotation

Defined by option "**backup_rotation**".

It will remove the old snapshots from the local filesystem. 

MyBackup can rotate the old snapshots based on a relative time indicating a time reference like for example, "1 day", "2 days", "3 weeks", "1 month", etc.

MyBackup can also rotate the old snapshots based on the backup sequence using just a number. So for example, 2 will indicate that MyBackup will keep the last two previous backups.

When this option is missing, the rotation is disabled.


### Snapshot compression

Defined by option "**compress**".

It will compress the snapshot file as a GZ file. The ".gz" extension will be added to a snapshot file.

The snapshot file is streamed directly without the requirement of an intermediate dump file, so it reduces the disk space during the dump stage.

This option can accept alternative an integer from 1 to 9 that indicates the gzip compression level.

By default, compress is equal to "false" so the snapshot file is not compressed.


### Check replication

Defined by option "**is_replica**".

If the backup is taken from a read-replica, it's recommended to check if the replica is running before performing the backup, so it will avoid dumping snapshots from a server that is not synchronized with the master.

When this option is equal to `true` and the read-replica is not running or not reading the binlogs from the master server it will stop the backup process.


### Define objets to dump

Defined by the option "**databases**".

Define which databases or selected tables are dumped into the snapshot.

See [Backup plan and options for examples](#backup-plan-and-options).


### Snapshot Encryption

Defined by option "**encryptation**".

It will encrypt the snapshot file. The ".aes" extension will be added to the snapshot file name.


### Notifications

Defined by the option "**notifications**".

It defines the notifications methods so backups are reported when the process is finished.

Only "slack" and "mail" methods are available, and both methods can be used at the same time.

Example:

```YAML
notifications:
  slack:
    username: 'BackupBot'
    channel: "[CHANNELID]"
    webhook: "https://hooks.slack.com/services/[ID]"
  mail:
    address: "example@example.net"       
    host: '127.0.0.1'
    port: 2525
    encryption: 'tls'
    username: ''
    password: ''
    timeout: null
```

### Filesystems

Defined by option "**filesystems**".

It defines multiple different filesystems that can be used by the "post_actions".

The supported filesystems are:
- Google Cloud Storage
- S3
- FTP
- SFTP
- local

The local filesystem is automatically registered, and it's pointing to the directory where the snapshot is located.

Example:

```YAML
filesystems:
   gcloud:
      driver: gcs
      project_id: "my_project"
      bucket: "mybackups"
      key_file:
         type: "service_account"
         private_key_id: ""
         private_key: ""
         client_email: ""
         client_id: ""
         auth_uri: ""
         token_uri: ""
         auth_provider_x509_cert_url: ""
         client_x509_cert_url: ""
   s3:
      driver: s3
      key: ''
      secret: ''
      region: 'eu-central-1'
      bucket: 'mybackup-bucket'
   ftp:
      driver: ftp
      host: 'localhost'
      username: 'username'
      password: 'secret'
   sftp:
      driver: 'sftp'
      host: 'localhost'
      username: 'username'
      password: 'secret'
      privateKey: '/foo/bar/.ssh/id_rsa'
      passphrase: 'secret'
      # Settings for file / directory permissions...
      visibility: 'private'            # `private` = 0600, `public` = 0644
      directory_visibility: 'private'  # `private` = 0700, `public` = 0755
      # Optional SFTP Settings...
      # hostFingerprint: ''
      # maxTries: 4
      # passphrase: ''
      # port: 22
      # root: ''
      # timeout: 30,
      # useAgent: true
```

### Post actions

Defined by "**post_actions**".

It will perform additional operations after the full snapshot was dumped.

The available post actions are:
- copy (Copy a file).
- delete_old (Delete an old file).
- run (Run a shell command).

## Environment variables

It's possible to use environment variables as part of the configuration.

Environment variables can be defined using the following pattern "%env(KEY:DEFAULT_VALUE)%".

Example:

      password: '%env(DB_PASSWORD:secret)%'

In the previous example the "password" is taken from the environment variable "DB_PASSWORD" and in case the variable is undefined, the default value "secret" is used.

The default value is optional.

### Cast environment variables

It's also possible to cast the environment values.

Example:

      compress: '%env(bool->COMPRESS)%'

In the previous example the "COMPRESS" environment variable is taken and cast as boolean.

The following casts are available:

- string (Default)
- bool (When a variable is equal to "true" or 1 then is cast as true)
- int
- float
- json (Convert a JSON string into an array)

In the following example we can provide the connection details using one environment variable:

      connection: '%env(json->CONNECTION)%'

the "CONNECTION" environment variable can have a value like the following one:

      CONNECTION={"driver":"mysql","host":"localhost","port":3306,"username":"root","password":"secret"}


## Placeholders

File names can be automatically generated using placeholders. The following placeholders are available:

### Date and time placeholders
- {{date}} - It will generate the current date in format YYYY-mm-dd.
- {{datetime}} - It will generate the current date and time using the format YYYY-mm-dd HH:ii:ss.
- {{timestamp}} - It will generate the current UNIX timestamp.
- {{date_calc:}} - It will generate a relative date and time using the format YYYY-mm-dd HH:ii:ss. Example: {{date_calc:-2days}} or {{date_calc:+1month}}.
- {{uuid}} - It will generate a UUID7. 

### Special placeholders
- {{snapshot_file}} - It's replaced with the final snapshot file name with all the final extensions. It cannot be used into the **snapshot_file** option.

### Format placeholders

- {{date:}} - It will truncate a datetime format as date. Example: {{date:2024-01-01 01:00:00}} will generate 2024-01-01.
- {{date_format:|}} - Customized date [format](https://www.php.net/manual/en/datetime.format.php). Example: {{date_format:2024-03-02|d-m-Y}} will generate 02-03-2024. 
- {{datetime:}} - It will truncate a date as datetime. Example: {{datetime:2024-01-01}} will generate 2024-01-01 00:00:00 where "00:00:00" will become the current time (It's the shortcut of {{date_format:2024-01-01|Y-m-d h:i:s}}).
- {{numeric:}} - It will remove all non-numeric characters. Example: {{numeric:2024-01-01}} will generate 20240101.
- {{basename:}} - It will extract the basename of a path. Example: {{basename:/foo/var/file.sql}} is replaced by "file.sql".

### Placeholders chain

Placeholders can be combined.

Examples:

- {{basename:{{snapshot_file}}}} - It will extract the basename of the snapshot path.
- {{date:{{date_calc:-1week}} - It will extract the date in format YYYY-mm-dd of the {{date_calc:-1week}} result.
- {{numeric:{{datetime}}} - It removes all non-numeric characters of the {{datetime}} result.
- {{date_format:{{date}}|Y}} - It will extract the year of the current date.
