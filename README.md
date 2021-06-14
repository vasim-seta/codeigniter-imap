# IMAP Library for CodeIgniter

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Total Downloads][ico-downloads]][link-downloads]

## Description

CodeIgniter IMAP is an easy way to integrate the native php imap library into your **CodeIgniter** app.

Note: this library is forked from webklex/laravel-imap which was imap library for laravel, but i need to use it in CodeIgniter and without few changes in the library i can't use it in CodeIgniter. 
So, I have copied it and changed it as needed. but then i got idea to publish it because there maybe lots of people who wants to use that laravel library in CodeIgniter without doing any changes.
Also i have changed things which i have used and needed, there might be issue or error in other things, there will be few trash files and methods of/for laravel as well.
If you got any issue then please feel free to report that or you can fork this and do it your self.
I have also tried to use webklex/php-imap but i was unable to use it because of few errors and i didn't have much time to figure that out.
I have never created any library and i don't know about licencing much more, so please let me know if any issue regarding licencing or anything else then i will remove it from here.

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Documentation](#documentation)
  - [Client::class](#clientclass)
  - [Message::class](#messageclass)
  - [Folder::class](#folderclass)
  - [Attachment::class](#attachmentclass) 
  - [MessageCollection::class](#messagecollectionclass) 
  - [AttachmentCollection::class](#attachmentcollectionclass) 
  - [FolderCollection::class](#foldercollectionclass) 
- [Known issues](#known-issues)
- [Milestones & upcoming features](#milestones--upcoming-features)
- [Security](#security)
- [Credits](#credits)
- [Supporters](#supporters)
- [License](#license)

## Installation

1) Install the php-imap library if it isn't already installed:

``` shell
sudo apt-get install php*-imap && sudo apache2ctl graceful
```

You might also want to check `phpinfo()` if the extension is enabled.

2) Now install the CodeIgniter IMAP package by running the following command:

``` shell
composer require vasim-seta/codeigniter-imap
```

## Configuration

when there is no setting define while calling IMAP/Client then it will use these default settings, you might want to add the following to
your `application/config/config.php` file.

```
$config['imap']['host']             = 'localhost';
$config['imap']['port']             = 993;
$config['imap']['encryption']       = 'ssl';
$config['imap']['validate_cert']    = TRUE;
$config['imap']['username']         = 'root@example.com';
$config['imap']['password']         = '';

$config['imap.options.open']        = [];
$config['imap.options.delimiter']   = '/';
```

The following encryption methods are supported:
- `false` &mdash; Disable encryption 
- `ssl` &mdash; Use SSL
- `tls` &mdash; Use TLS

Detailed [application/config/config.php] configuration:
 - `host` &mdash; imap host
 - `port` &mdash; imap port
 - `encryption` &mdash; desired encryption method
 - `validate_cert` &mdash; decide weather you want to verify the certificate or not
 - `username` &mdash; imap account username
 - `password` &mdash; imap account password
 - `imap.options` &mdash; additional fetch options
   - `delimiter` &mdash; you can use any supported char such as ".", "/", etc
   - `fetch` &mdash; `FT_UID` (message marked as read by fetching the message) or `FT_PEEK` (fetch the message without setting the "read" flag)
   - `fetch_body` &mdash; If set to `false` all messages will be fetched without the body and any potential attachments
   - `fetch_attachment` &mdash;  If set to `false` all messages will be fetched without any attachments
   - `open` &mdash; special configuration for imap_open()
     - `DISABLE_AUTHENTICATOR` &mdash; Disable authentication properties.

## Usage

This is a basic example, which will echo out all Mails within all imap folders
and will move every message into INBOX.read. Please be aware that this should not ben
tested in real live but it gives an impression on how things work.

``` php

$oClient = new \Vasim\IMAP\Client([
    'host'          => 'somehost.com',
    'port'          => 993,
    'encryption'    => 'ssl',
    'validate_cert' => true,
    'username'      => 'username',
    'password'      => 'password',
]);

//Connect to the IMAP Server
$oClient->connect();

//Get all Mailboxes
/** @var \Vasim\IMAP\Support\FolderCollection $aFolder */
$aFolder = $oClient->getFolders();

//Loop through every Mailbox
/** @var \Vasim\IMAP\Folder $oFolder */
foreach($aFolder as $oFolder){

    //Get all Messages of the current Mailbox $oFolder
    /** @var \Vasim\IMAP\Support\MessageCollection $aMessage */
    $aMessage = $oFolder->getMessages();
    
    /** @var \Vasim\IMAP\Message $oMessage */
    foreach($aMessage as $oMessage){
        echo $oMessage->subject.'<br />';
        echo 'Attachments: '.$oMessage->getAttachments()->count().'<br />';
        echo $oMessage->getHTMLBody(true);
        
        //Move the current Message to 'INBOX.read'
        if($oMessage->moveToFolder('INBOX.read') == true){
            echo 'Message has ben moved';
        }else{
            echo 'Message could not be moved';
        }
    }
}
```

There is an experimental function available to get a Folder instance by name. 
For an easier access please take a look at the new config option `imap.options.delimiter` however the `getFolder` 
method takes three options: the required (string) $folder_name and two optional variables. An integer $attributes which 
seems to be sometimes 32 or 64 (I honestly have no clue what this number does, so feel free to enlighten me and anyone 
else) and a delimiter which if it isn't set will use the default option configured inside the [config/imap.php](src/config/imap.php) file.
``` php
/** @var \Vasim\IMAP\Client $oClient */

/** @var \Vasim\IMAP\Folder $oFolder */
$oFolder = $oClient->getFolder('INBOX.name');
```

Search for specific emails:
``` php
/** @var \Vasim\IMAP\Folder $oFolder */

//Get all messages since march 15 2018
/** @var \Vasim\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->searchMessages([['SINCE', Carbon::parse('15.03.2018')]]);

//Get all messages containing "hello world"
/** @var \Vasim\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->searchMessages([['TEXT', 'hello world']]);

//Get all unseen messages containing "hello world"
/** @var \Vasim\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->searchMessages([['UNSEEN'], ['TEXT', 'hello world']]);
```

Available search criteria:
- `ALL` &mdash; return all messages matching the rest of the criteria
- `ANSWERED` &mdash; match messages with the \\ANSWERED flag set
- `BCC` "string" &mdash; match messages with "string" in the Bcc: field
- `BEFORE` "date" &mdash; match messages with Date: before "date"
- `BODY` "string" &mdash; match messages with "string" in the body of the message
- `CC` "string" &mdash; match messages with "string" in the Cc: field
- `DELETED` &mdash; match deleted messages
- `FLAGGED` &mdash; match messages with the \\FLAGGED (sometimes referred to as Important or Urgent) flag set
- `FROM` "string" &mdash; match messages with "string" in the From: field
- `KEYWORD` "string" &mdash; match messages with "string" as a keyword
- `NEW` &mdash; match new messages
- `OLD` &mdash; match old messages
- `ON` "date" &mdash; match messages with Date: matching "date"
- `RECENT` &mdash; match messages with the \\RECENT flag set
- `SEEN` &mdash; match messages that have been read (the \\SEEN flag is set)
- `SINCE` "date" &mdash; match messages with Date: after "date"
- `SUBJECT` "string" &mdash; match messages with "string" in the Subject:
- `TEXT` "string" &mdash; match messages with text "string"
- `TO` "string" &mdash; match messages with "string" in the To:
- `UNANSWERED` &mdash; match messages that have not been answered
- `UNDELETED` &mdash; match messages that are not deleted
- `UNFLAGGED` &mdash; match messages that are not flagged
- `UNKEYWORD` "string" &mdash; match messages that do not have the keyword "string"
- `UNSEEN` &mdash; match messages which have not been read yet

Further information:
- http://php.net/manual/en/function.imap-search.php
- https://tools.ietf.org/html/rfc1176
- https://tools.ietf.org/html/rfc1064
- https://tools.ietf.org/html/rfc822
     

Get a specific message by uid (Please note that the uid is not unique and can change):
``` php
/** @var \Vasim\IMAP\Folder $oFolder */

/** @var \Vasim\IMAP\Message $oMessage */
$oMessage = $oFolder->getMessage($uid = 1);
```

Flag or "unflag" a message:
``` php
/** @var \Vasim\IMAP\Message $oMessage */
$oMessage->setFlag(['Seen', 'Spam']);
$oMessage->unsetFlag('Spam');
```

Save message attachments:
``` php
/** @var \Vasim\IMAP\Message $oMessage */

/** @var \Vasim\IMAP\Support\AttachmentCollection $aAttachment */
$aAttachment = $oMessage->getAttachments();

$aAttachment->each(function ($oAttachment) {
    /** @var \Vasim\IMAP\Attachment $oAttachment */
    $oAttachment->save();
});
```

Fetch messages without body fetching (decrease load):
``` php
/** @var \Vasim\IMAP\Folder $oFolder */

/** @var \Vasim\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->searchMessages([['TEXT', 'Hello world']], null, false);

/** @var \Vasim\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->getMessages('ALL', null, false);
```

Fetch messages without body and attachment fetching (decrease load):
``` php
/** @var \Vasim\IMAP\Folder $oFolder */

/** @var \Vasim\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->searchMessages([['TEXT', 'Hello world']], null, false, 'UTF-8', false);

/** @var \Vasim\IMAP\Support\MessageCollection $aMessage */
$aMessage = $oFolder->getMessages('ALL', null, false, false);
```

Find the folder containing a message:
``` php
$oFolder = $aMessage->getContainingFolder();
```

## Documentation
### [Client::class](src/IMAP/Client.php)
| Method              | Arguments                                                                       | Return            | Description                                                                                                                   |
| ------------------- | ------------------------------------------------------------------------------- | :---------------: | ----------------------------------------------------------------------------------------------------------------------------  |
| setConfig           | array $config                                                                   | self              | Set the Client configuration. Take a look at `config/imap.php` for more inspiration.                                          |
| getConnection       | resource $connection                                                            | resource          | Get the current imap resource                                                                                                 |
| setReadOnly         | bool $readOnly                                                                  | self              | Set read only property and reconnect if it's necessary.                                                                       |
| setFetchOption      | integer $option                                                                 | self              | Fail proof setter for $fetch_option                                                                                           |
| isReadOnly          |                                                                                 | bool              | Determine if connection is in read only mode.                                                                                 |
| isConnected         |                                                                                 | bool              | Determine if connection was established.                                                                                      |
| checkConnection     |                                                                                 |                   | Determine if connection was established and connect if not.                                                                   |
| connect             | int $attempts                                                                   |                   | Connect to server.                                                                                                            |
| disconnect          |                                                                                 |                   | Disconnect from server.                                                                                                       |
| getFolder           | string $folder_name, int $attributes = 32, int or null $delimiter               | Folder            | Get a Folder instance by name                                                                                                 |
| getFolders          | bool $hierarchical, string or null $parent_folder                               | FolderCollection  | Get folders list. If hierarchical order is set to true, it will make a tree of folders, otherwise it will return flat array.  |
| openFolder          | Folder $folder, integer $attempts                                               |                   | Open a given folder.                                                                                                          |
| createFolder        | string $name                                                                    | boolean           | Create a new folder.                                                                                                          |
| renameFolder        | string $old_name, string $new_name                                              | boolean           | Rename a folder. |
| deleteFolder        | string $name                                                                    | boolean           | Delete a folder. |
| getMessages         | Folder $folder, string $criteria, bool $fetch_body, bool $fetch_attachment      | MessageCollection | Get messages from folder.                                                                                                     |
| getUnseenMessages   | Folder $folder, string $criteria, bool $fetch_body, bool $fetch_attachment      | MessageCollection | Get Unseen messages from folder.                                                                                              |
| searchMessages      | array $where, Folder $folder, $fetch_options, bool $fetch_body, string $charset, bool $fetch_attachment | MessageCollection | Get specific messages from a given folder.                                                                                    |
| getQuota            |                                                                                 | array             | Retrieve the quota level settings, and usage statics per mailbox                                                              |
| getQuotaRoot        | string $quota_root                                                              | array             | Retrieve the quota settings per user                                                                                          |
| countMessages       |                                                                                 | int               | Gets the number of messages in the current mailbox                                                                            |
| countRecentMessages |                                                                                 | int               | Gets the number of recent messages in current mailbox                                                                         |
| getAlerts           |                                                                                 | array             | Returns all IMAP alert messages that have occurred                                                                            |
| getErrors           |                                                                                 | array             | Returns all of the IMAP errors that have occurred                                                                             |
| getLastError        |                                                                                 | string            | Gets the last IMAP error that occurred during this page request                                                               |
| expunge             |                                                                                 | bool              | Delete all messages marked for deletion                                                                                       |
| checkCurrentMailbox |                                                                                 | object            | Check current mailbox                                                                                                         |

### [Message::class](src/IMAP/Message.php)
| Method          | Arguments                     | Return               | Description                            |
| --------------- | ----------------------------- | :------------------: | -------------------------------------- |
| parseBody       |                               | Message              | Parse the Message body                 |
| delete          |                               |                      | Delete the current Message             |
| restore         |                               |                      | Restore a deleted Message              |
| copy            | string $mailbox, int $options |                      | Copy the current Messages to a mailbox |
| move            | string $mailbox, int $options |                      | Move the current Messages to a mailbox |
| getContainingFolder | Folder or null $folder    | null|Folder          | Get the folder containing the message  |
| moveToFolder    | string $mailbox, int $options |                      | Move the Message into an other Folder  |
| setFlag         | string or array $flag         | boolean              | Set one or many flags                  |
| unsetFlag       | string or array $flag         | boolean              | Unset one or many flags                |
| hasTextBody     |                               |                      | Check if the Message has a text body   |
| hasHTMLBody     |                               |                      | Check if the Message has a html body   |
| getTextBody     |                               | string               | Get the Message text body              |
| getHTMLBody     |                               | string               | Get the Message html body              |
| getAttachments  |                               | AttachmentCollection | Get all message attachments            |
| hasAttachments  |                               | boolean              | Checks if there are any attachments present            |
| getClient       |                               | Client               | Get the current Client instance        |
| getUid          |                               | string               | Get the current UID                    |
| getFetchOptions |                               | string               | Get the current fetch option           |
| getMsglist      |                               | integer              | Get the current message list           |
| getHeaderInfo   |                               | object               | Get the current header_info object     |
| getHeader       |                               | string               | Get the current raw header             |
| getMessageId    |                               | integer              | Get the current message ID             |
| getMessageNo    |                               | integer              | Get the current message number         |
| getSubject      |                               | string               | Get the current subject                |
| getReferences   |                               | mixed                | Get any potentially present references |
| getDate         |                               | Carbon               | Get the current date object            |
| getFrom         |                               | array                | Get the current from information       |
| getTo           |                               | array                | Get the current to information         |
| getCc           |                               | array                | Get the current cc information         |
| getBcc          |                               | array                | Get the current bcc information        |
| getReplyTo      |                               | array                | Get the current reply to information   |
| getInReplyTo    |                               | string               | Get the current In-Reply-To            |
| getSender       |                               | array                | Get the current sender information     |
| getBodies       |                               | mixed                | Get the current bodies                 |
| getRawBody      |                               | mixed                | Get the current raw message body       |
| is              |                               | boolean              | Does this message match another one?   |

### [Folder::class](src/IMAP/Folder.php)
| Method            | Arguments                                                                           | Return            | Description                                    |
| ----------------- | ----------------------------------------------------------------------------------- | :---------------: | ---------------------------------------------- |
| hasChildren       |                                                                                     | bool              | Determine if folder has children.              |
| setChildren       | array $children                                                                     | self              | Set children.                                  |
| getMessage        | integer $uid, integer or null $msglist, int or null fetch_options, bool $fetch_body, bool $fetch_attachment | Message           | Get a specific message from folder.            |
| getMessages       | string $criteria, bool $fetch_body, bool $fetch_attachment                                                  | MessageCollection | Get messages from folder.                      |
| getUnseenMessages | string $criteria, bool $fetch_body, bool $fetch_attachment                                                  | MessageCollection | Get Unseen messages from folder.               |
| searchMessages    | array $where, $fetch_options, bool $fetch_body, string $charset, bool $fetch_attachment                     | MessageCollection | Get specific messages from a given folder.     |
| delete            |                                                                                     |                   | Delete the current Mailbox                     |
| move              | string $mailbox                                                                     |                   | Move or Rename the current Mailbox             |
| getStatus         | integer $options                                                                    | object            | Returns status information on a mailbox        |
| appendMessage     | string $message, string $options, string $internal_date                             | bool              | Append a string message to the current mailbox |
| getClient         |                                                                                     | Client            | Get the current Client instance                |
                    
### [Attachment::class](src/IMAP/Attachment.php)
| Method         | Arguments                      | Return         | Description                                            |
| -------------- | ------------------------------ | :------------: | ------------------------------------------------------ |
| getContent     |                                | string or null | Get attachment content                                 |     
| getMimeType    |                                | string or null | Get attachment mime type                               |     
| getExtension   |                                | string or null | Get a guessed attachment extension                     |     
| getName        |                                | string or null | Get attachment name                                    |        
| getType        |                                | string or null | Get attachment type                                    |        
| getDisposition |                                | string or null | Get attachment disposition                             | 
| getContentType |                                | string or null | Get attachment content type                            | 
| getImgSrc      |                                | string or null | Get attachment image source as base64 encoded data url |      
| save           | string $path, string $filename | boolean        | Save the attachment content to your filesystem         |      

### [MessageCollection::class](src/IMAP/Support/MessageCollection.php)
Extends [Illuminate\Support\Collection::class](https://laravel.com/api/5.4/Illuminate/Support/Collection.html)

| Method   | Arguments                                           | Return               | Description                      |
| -------- | --------------------------------------------------- | :------------------: | -------------------------------- |
| paginate | int $perPage = 15, $page = null, $pageName = 'page' | LengthAwarePaginator | Paginate the current collection. |

### [AttachmentCollection::class](src/IMAP/Support/AttachmentCollection.php)
Extends [Illuminate\Support\Collection::class](https://laravel.com/api/5.4/Illuminate/Support/Collection.html)

| Method   | Arguments                                           | Return               | Description                      |
| -------- | --------------------------------------------------- | :------------------: | -------------------------------- |
| paginate | int $perPage = 15, $page = null, $pageName = 'page' | LengthAwarePaginator | Paginate the current collection. |

### [FolderCollection::class](src/IMAP/Support/FolderCollection.php)
Extends [Illuminate\Support\Collection::class](https://laravel.com/api/5.4/Illuminate/Support/Collection.html)

| Method   | Arguments                                           | Return               | Description                      |
| -------- | --------------------------------------------------- | :------------------: | -------------------------------- |
| paginate | int $perPage = 15, $page = null, $pageName = 'page' | LengthAwarePaginator | Paginate the current collection. |

### Known issues

## Milestones & upcoming features
* Wiki!!

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Security

If you discover any security related issues, please email vasimseta786@gmail.com instead of using the issue tracker.

## Credits

- [Vasim][link-author]
- [All Contributors][link-contributors]

## Supporters


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
