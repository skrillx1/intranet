# Google Calendar Service

Google Calendar Service provides the functionality to create the calendar 
and sync calendar events in the Drupal system.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/google_calendar_service).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/google_calendar_service?categories=All).


## Table of contents

- Requirements
- Installation
- Configuration
- Styles
- Maintainers


## Requirements

Run `composer require google/apiclient` before installation.

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

For example, to install the latest 8.x-2.x-dev release, you may run:

`composer require 'drupal/google_calendar_service:2.x-dev@dev'`

## Configuration

1. Navigate to "/admin/config/google-calendar-service/settings"
2. Go to https://console.developers.google.com/projectcreate and create your
  project, you can find more detailed instructions in [Step 1: Create a project](https://support.google.com/a/answer/7378726?hl=en)
3. You will neeed to create a service account in your project, in the project's Dashboard, click on "APIs & Services" and follow the instructions in [Step 4: Step 4: Create the service account](https://support.google.com/a/answer/7378726?hl=en)
4. Update the calendar you want to use for the module, in the google calendar settings, look for the "Share with specific people or groups" option, and add a new user under "Share with specific people", enter the service account email address, you can find this e-mail address in the "Service account details" page (the service account e-mail should look like this: my-project-44119@x-jigsaw-909414.iam.gserviceaccount.com)
5. Then go to "/admin/config/google-calendar-service/settings" and upload the JSON file you just created in step 3, and use the service account e-mail in the "Google User Email" field (NOT your google e-mail).
6. Go to /calendar/add and add the calendar you want to use:
  Name: Add the name you want for the calendar, this will be the name of the Calendar Entity in the Drupal site.
  Google Calendar ID: Your google e-mail
7. Click on "Save" and then go to /calendar, you will see the calendar you just created in step 6, under the "Operations" column, click on "Import Events", it will take a bit for the events to be imported, after that you should be able to see all the events in that calendar.

## Maintainers

- OPTASY - [optasy](https://www.drupal.org/u/optasy)
- Adrian ABABEI - [web247](https://www.drupal.org/u/web247)
- Daniel Rodriguez - [danrod](https://www.drupal.org/u/danrod)
- Nicolae Procopan - [thebumik](https://www.drupal.org/u/thebumik)
- skaught - [SKAUGHT](https://www.drupal.org/u/skaught)
