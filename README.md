# DATA SENDER

Notification System

## Description

This system detects high temperature and smoke levels using IoT sensors and sends real-time fire alerts to Firebase Realtime Database. Notifications are sent via Firebase Cloud Messaging to subscribed Android devices.

## Features

- Real-time Fire Event Logging
- Firebase Realtime Database Integration
- Cloud Messaging Alerts (FCM)
- Legacy PHP Bridge Support

## Technologies

- Python 3.x
- PHP 8.x
- Firebase Realtime Database
- Firebase Cloud Messaging (FCM)
- Composer (PHP dependencies)

## Folder Structure

python-backend/ # main backend scripts
php-bridge/ # legacy PHP bridge
.gitignore
README.md


## Security Note

`serviceAccountKey.json` is **not included** in this repository. To run the project:

1. Generate a new Firebase Service Account Key.
2. Place it in the root folder as `serviceAccountKey.json`.