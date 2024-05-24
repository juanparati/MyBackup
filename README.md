       __  ___     ___           __           
      /  |/  /_ __/ _ )___ _____/ /____ _____
     / /|_/ / // / _  / _ `/ __/  '_/ // / _ \
    /_/  /_/\_, /____/\_,_/\__/_/\_\\_,_/ .__/
           /___/ MySQL/MariaDB backup  /_/

# MyBackup

## What is it?

A backup tool for MySQL/MariaDB that support the following features:

- Backup plan configuration (Copy entire databases or specific tables with custom options).
- GZ compression.
- Snapshot encryption.
- Snapshot rotation.
- Notifications (Slack and e-mail).
- Read-replica verification (Verify that you read replica is synchronised before performing a backup).
- Custom actions (Copy/Move/Remove between physical and cloud filesystems).
- Dynamic placeholders (Set snapshot names based on date, time, uuid, etc).
- Process lock (It avoids to overlap the same backup process).

## How it works?

### Perform backups

1. Create backup plan configuration:

        mybackup init backup_plan.yaml

2. Edit the backup plan configuration file.
3. Run the backup process:
    
        mybackup backup backup_plan.yaml

### Backup plan example

```YAML
name: "Backup plan"
catalog_file: "/tmp/catalog.sqlite"                         # Path to the snapshots catalog
snapshot_file: "/tmp/snapshot_{{numeric:{{datetime}}}}.sql" # Path to the snapshot
connection:
    driver: "mysql"                                   # Supported drivers are "mysql" and "mariadb"                                                                                       
    host: "localhost"
    port: 3306
    username: "root"
    password: "secret"                                # When this line is missing it will prompt the password
mysqldump_path: "mysqldump"                           # Path to mysqldump/mariadb-dump                             
backup_rotation: "2 days"                             # It will delete the backups older than 2 days
compress: true                                        # It will compress the snapshot file                
is_replica: true                                      # It will check if read-replica is synchronised                             
databases:
  - firstdb                                           # It will dump the entire "firstdb" database
  - secondb:                                          # It will dump "seconddb" database with custom options and ignoring some tables
      options: ["--single-transaction", "--quick"]
      ignore: ["table1", "table2"]
  - thirddb:                                          # It will dump some tables of "thirddb" database.
      options: ["--single-transaction", "--quick"]
      tables:
        - table1:
            where: "id >= 1000000"
        - table2
        - table3:
            where: "created_at >= DATE(DATE_SUB(NOW(), INTERVAL 1 MONTH))"
notifications:
  slack:
    username: 'BackupBot'
    channel: "[CHANNELID]"
    webhook: "https://hooks.slack.com/services/[ID]"
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
post_actions:
   - copy:                            # Copy snapshot file to gcloud filesystem
        filesystem: gcloud
        source: '{{snapshot_file}}'
        destination: '{{basename:{{snapshot_file}}}}'
   - copy:                            # Copy snapshot file to s3 filesystem
        filesystem: s3
        source: '{{snapshot_file}}'
        destination: '{{uuid}}.sql.gz.aes'
   - delete_old:
        filesystem: gcloud
        pattern: 'snapshot_test_*'
        period: '3 days'
```



