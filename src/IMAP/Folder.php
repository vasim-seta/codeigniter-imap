<?php
/*
* File:     Folder.php
* Category: -
* Author:   M. Goldenbaum
* Created:  19.01.17 22:21
* Updated:  -
*
* Description:
*  -
*/

namespace Vasim\IMAP;

use Vasim\IMAP\Exceptions\GetMessagesFailedException;
use Vasim\IMAP\Exceptions\MessageSearchValidationException;
use Vasim\IMAP\Support\FolderCollection;
use Vasim\IMAP\Support\MessageCollection;

/**
 * Class Folder
 *
 * @package Vasim\IMAP
 */
class Folder {

    /**
     * Client instance
     *
     * @var \Vasim\IMAP\Client
     */
    protected $client;

    /**
     * Folder full path
     *
     * @var string
     */
    public $path;

    /**
     * Folder name
     *
     * @var string
     */
    public $name;

    /**
     * Folder fullname
     *
     * @var string
     */
    public $fullName;

    /**
     * Children folders
     *
     * @var FolderCollection|array
     */
    public $children = [];

    /**
     * Delimiter for folder
     *
     * @var string
     */
    public $delimiter;

    /**
     * Indicates if folder can't containg any "children".
     * CreateFolder won't work on this folder.
     *
     * @var boolean
     */
    public $no_inferiors;

    /**
     * Indicates if folder is only container, not a mailbox - you can't open it.
     *
     * @var boolean
     */
    public $no_select;

    /**
     * Indicates if folder is marked. This means that it may contain new messages since the last time it was checked.
     * Not provided by all IMAP servers.
     *
     * @var boolean
     */
    public $marked;

    /**
     * Indicates if folder containg any "children".
     * Not provided by all IMAP servers.
     *
     * @var boolean
     */
    public $has_children;

    /**
     * Indicates if folder refers to other.
     * Not provided by all IMAP servers.
     *
     * @var boolean
     */
    public $referal;

    /**
     * Folder constructor.
     *
     * @param \Vasim\IMAP\Client $client
     *
     * @param object $folder
     */
    public function __construct(Client $client, $folder) {
        $this->client = $client;

        $this->delimiter = $folder->delimiter;
        $this->path      = $folder->name;
        $this->fullName  = $this->decodeName($folder->name);
        $this->name      = $this->getSimpleName($this->delimiter, $this->fullName);

        $this->parseAttributes($folder->attributes);
    }

    /**
     * Determine if folder has children.
     *
     * @return bool
     */
    public function hasChildren() {
        return $this->has_children;
    }

    /**
     * Set children.
     *
     * @param FolderCollection|array $children
     *
     * @return self
     */
    public function setChildren($children = []) {
        $this->children = $children;

        return $this;
    }

    /**
     * Get a specific message by UID
     *
     * @param integer      $uid     Please note that the uid is not unique and can change
     * @param integer|null $msglist
     * @param integer|null $fetch_options
     * @param boolean      $fetch_body
     * @param boolean      $fetch_attachment
     *
     * @return Message|null
     */
    public function getMessage($uid, $msglist = null, $fetch_options = null, $fetch_body = false, $fetch_attachment = false) {
        if (imap_msgno($this->getClient()->getConnection(), $uid) > 0) {
            return new Message($uid, $msglist, $this->getClient(), $fetch_options, $fetch_body, $fetch_attachment);
        }

        return null;
    }

    /**
     * Get all messages
     *
     * @param string    $criteria
     * @param int|null  $fetch_options
     * @param boolean   $fetch_body
     * @param boolean   $fetch_attachment
     *
     * @return MessageCollection
     * @throws Exceptions\ConnectionFailedException
     * @throws GetMessagesFailedException
     * @throws MessageSearchValidationException
     */
    public function getMessages($criteria = 'ALL', $fetch_options = null, $fetch_body = true, $fetch_attachment = true) {
        return $this->searchMessages([[$criteria]], $fetch_options, $fetch_body, $fetch_attachment);
    }

    /**
     * Get all unseen messages
     *
     * @param string    $criteria
     * @param int|null  $fetch_options
     * @param boolean   $fetch_body
     * @param boolean   $fetch_attachment
     *
     * @return MessageCollection
     * @throws Exceptions\ConnectionFailedException
     * @throws GetMessagesFailedException
     * @throws MessageSearchValidationException
     *
     * @deprecated 1.0.5:2.0.0 No longer needed. Use Folder::getMessages('UNSEEN') instead
     * @see Folder::getMessages()
     */
    public function getUnseenMessages($criteria = 'UNSEEN', $fetch_options = null, $fetch_body = true, $fetch_attachment = true) {
        return $this->getMessages($criteria, $fetch_options, $fetch_body, $fetch_attachment);
    }

    /**
     * Search messages by a given search criteria
     *
     * @param array   $where  Is a two dimensional array where each array represents a criteria set:
     *                        ---------------------------------------------------------------------------------------
     *                        The following sample would search for all messages received from someone@example.com or
     *                        contain the text "Hello world!":
     *                        [['FROM' => 'someone@example.com'],[' TEXT' => 'Hello world!']]
     *                        ---------------------------------------------------------------------------------------
     *                        The following sample would search for all messages received since march 15 2018:
     *                        [['SINCE' => Carbon::parse('15.03.2018')]]
     *                        ---------------------------------------------------------------------------------------
     *                        The following sample would search for all flagged messages:
     *                        [['FLAGGED']]
     *                        ---------------------------------------------------------------------------------------
     * @param int|null  $fetch_options
     * @param boolean   $fetch_body
     * @param string    $charset
     * @param boolean   $fetch_attachment
     *
     * @return MessageCollection
     *
     * @throws Exceptions\ConnectionFailedException
     * @throws GetMessagesFailedException
     * @throws MessageSearchValidationException
     *
     * @doc http://php.net/manual/en/function.imap-search.php
     *      imap_search() only supports IMAP2 search criterias, because the function mail_criteria() (from c-client lib)
     *      is used in ext/imap/php_imap.c for parsing the search string.
     *      IMAP2 search criteria is defined in RFC 1176, section "tag SEARCH search_criteria".
     *
     *      https://tools.ietf.org/html/rfc1176 - INTERACTIVE MAIL ACCESS PROTOCOL - VERSION 2
     *      https://tools.ietf.org/html/rfc1064 - INTERACTIVE MAIL ACCESS PROTOCOL - VERSION 2
     *      https://tools.ietf.org/html/rfc822  - STANDARD FOR THE FORMAT OF ARPA INTERNET TEXT MESSAGES
     *      Date and time example from RFC822:
     *      date-time   =  [ day "," ] date time        ; dd mm yy
     *                                                  ;  hh:mm:ss zzz
     *
     *      day         =  "Mon"  / "Tue" /  "Wed"  / "Thu" /  "Fri"  / "Sat" /  "Sun"
     *
     *      date        =  1*2DIGIT month 2DIGIT        ; day month year
     *                                                  ;  e.g. 20 Jun 82
     *
     *      month       =  "Jan"  /  "Feb" /  "Mar"  /  "Apr" /  "May"  /  "Jun" /  "Jul"  /  "Aug" /  "Sep"  /  "Oct" /  "Nov"  /  "Dec"
     *
     *      time        =  hour zone                    ; ANSI and Military
     *
     *      hour        =  2DIGIT ":" 2DIGIT [":" 2DIGIT] ; 00:00:00 - 23:59:59
     *
     *      zone        =  "UT"  / "GMT"         ; Universal Time
     *                                           ; North American : UT
     *                  =  "EST" / "EDT"         ;  Eastern:  - 5/ - 4
     *                  =  "CST" / "CDT"         ;  Central:  - 6/ - 5
     *                  =  "MST" / "MDT"         ;  Mountain: - 7/ - 6
     *                  =  "PST" / "PDT"         ;  Pacific:  - 8/ - 7
     *                  =  1ALPHA                ; Military: Z = UT;
     *                                           ;  A:-1; (J not used)
     *                                           ;  M:-12; N:+1; Y:+12
     *                  / ( ("+" / "-") 4DIGIT ) ; Local differential
     *                                           ;  hours+min. (HHMM)
     */
    public function searchMessages(array $where, $fetch_options = null, $fetch_body = true, $charset = "", $fetch_attachment = true) {

        $this->getClient()->checkConnection();

        if ($this->validateWhereStatements($where) === false) {
            throw new MessageSearchValidationException('Invalid imap search criteria provided');
        }

        try {
            $this->getClient()->openFolder($this);
            $messages = MessageCollection::make([]);

            $query = '';
            foreach ($where as $statement) {
                if (count($statement) == 1) {
                    $query .= $statement[0];
                } else {
                    $value = $statement[1];
                    if ($value instanceof \Carbon\Carbon) {
                        $value = $value->format('d M y');
                    }
                    $query .= $statement[0].' "'.$value.'"';
                }
                $query .= ' ';
            }

            $query = trim($query);

            $availableMessages = imap_search($this->getClient()->getConnection(), $query, SE_UID); //, $charset

            if ($availableMessages !== false) {
                $msglist = 1;
                foreach ($availableMessages as $msgno) {
                    $message = new Message($msgno, $msglist, $this->getClient(), $fetch_options, $fetch_body, $fetch_attachment);

                    $messages->put($message->getMessageId(), $message);
                    $msglist++;
                }
            }

            return $messages;
        } catch (\Exception $e) {
            $message = $e->getMessage();

            throw new GetMessagesFailedException($message);
        }
    }

    /**
     * Validate a given statement array
     *
     * @param array $statements
     *
     * @return bool
     *
     * @doc http://php.net/manual/en/function.imap-search.php
     *      https://tools.ietf.org/html/rfc1064
     *      https://tools.ietf.org/html/rfc822
     */
    protected function validateWhereStatements($statements) {
        foreach ($statements as $statement) {
            $criteria = $statement[0];
            if (in_array($criteria, [
                    'OR', 'AND',
                    'ALL', 'ANSWERED', 'BCC', 'BEFORE', 'BODY', 'CC', 'DELETED', 'FLAGGED', 'FROM', 'KEYWORD',
                    'NEW', 'OLD', 'ON', 'RECENT', 'SEEN', 'SINCE', 'SUBJECT', 'TEXT', 'TO',
                    'UNANSWERED', 'UNDELETED', 'UNFLAGGED', 'UNKEYWORD', 'UNSEEN']) === false) {
                return false;
            }
        }

        return empty($statements) === false;
    }

    /**
     * Decode name.
     * It converts UTF7-IMAP encoding to UTF-8.
     *
     * @param $name
     *
     * @return mixed|string
     */
    protected function decodeName($name) {
        preg_match('#\{(.*)\}(.*)#', $name, $preg);
        return mb_convert_encoding($preg[2], "UTF-8", "UTF7-IMAP");
    }

    /**
     * Get simple name (without parent folders).
     *
     * @param $delimiter
     * @param $fullName
     *
     * @return mixed
     */
    protected function getSimpleName($delimiter, $fullName) {
        $arr = explode($delimiter, $fullName);

        return end($arr);
    }

    /**
     * Parse attributes and set it to object properties.
     *
     * @param $attributes
     */
    protected function parseAttributes($attributes) {
        $this->no_inferiors = ($attributes & LATT_NOINFERIORS) ? true : false;
        $this->no_select    = ($attributes & LATT_NOSELECT) ? true : false;
        $this->marked       = ($attributes & LATT_MARKED) ? true : false;
        $this->referal      = ($attributes & LATT_REFERRAL) ? true : false;
        $this->has_children = ($attributes & LATT_HASCHILDREN) ? true : false;
    }

    /**
     * Delete the current Mailbox
     *
     * @return bool
     *
     * @throws Exceptions\ConnectionFailedException
     */
    public function delete() {
        $status = imap_deletemailbox($this->client->getConnection(), $this->path);
        $this->client->expunge();

        return $status;
    }

    /**
     * Move or Rename the current Mailbox
     *
     * @param string $target_mailbox
     *
     * @return bool
     *
     * @throws Exceptions\ConnectionFailedException
     */
    public function move($target_mailbox) {
        $status = imap_renamemailbox($this->client->getConnection(), $this->path, $target_mailbox);
        $this->client->expunge();

        return $status;
    }

    /**
     * Returns status information on a mailbox
     *
     * @param integer   $options
     *                  SA_MESSAGES     - set $status->messages to the number of messages in the mailbox
     *                  SA_RECENT       - set $status->recent to the number of recent messages in the mailbox
     *                  SA_UNSEEN       - set $status->unseen to the number of unseen (new) messages in the mailbox
     *                  SA_UIDNEXT      - set $status->uidnext to the next uid to be used in the mailbox
     *                  SA_UIDVALIDITY  - set $status->uidvalidity to a constant that changes when uids for the mailbox may no longer be valid
     *                  SA_ALL          - set all of the above
     *
     * @return object
     */
    public function getStatus($options) {
        return imap_status($this->client->getConnection(), $this->path, $options);
    }

    /**
     * Append a string message to the current mailbox
     *
     * @param string $message
     * @param string $options
     * @param string $internal_date
     *
     * @return bool
     */
    public function appendMessage($message, $options = null, $internal_date = null) {
        return imap_append($this->client->getConnection(), $this->path, $message, $options, $internal_date);
    }

    /**
     * Get the current Client instance
     *
     * @return Client
     */
    public function getClient() {
        return $this->client;
    }
}