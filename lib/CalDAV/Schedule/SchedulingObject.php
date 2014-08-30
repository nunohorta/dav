<?php

namespace Sabre\CalDAV\Schedule;

use Sabre\CalDAV\Backend;
use Sabre\DAV\Exception\MethodNotAllowed;

/**
 * The SchedulingObject represents a scheduling object in the Inbox collection
 *
 * @author Brett (https://github.com/bretten)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 * @copyright Copyright (C) 2007-2014 fruux GmbH. All rights reserved.
 */
class SchedulingObject extends \Sabre\DAV\File implements ISchedulingObject, \Sabre\DAVACL\IACL {

    /**
     /* The CalDAV backend
     *
     * @var Backend\SchedulingSupport
     */
    protected $caldavBackend;

    /**
     * Array with information about this SchedulingObject
     *
     * @var array
     */
    protected $objectData;

    /**
     * Constructor
     *
     * The following properties may be passed within $objectData:
     *
     *   * uri - A unique uri. Only the 'basename' must be passed.
     *   * principaluri - the principal that owns the object.
     *   * calendardata (optional) - The iCalendar data
     *   * etag - (optional) The etag for this object, MUST be encloded with
     *            double-quotes.
     *   * size - (optional) The size of the data in bytes.
     *   * lastmodified - (optional) format as a unix timestamp.
     *   * acl - (optional) Use this to override the default ACL for the node.
     *
     * @param Backend\BackendInterface $caldavBackend
     * @param array $objectData
     */
    function __construct(Backend\SchedulingSupport $caldavBackend,array $objectData) {

        $this->caldavBackend = $caldavBackend;

        if (!isset($objectData['uri'])) {
            throw new \InvalidArgumentException('The objectData argument must contain an \'uri\' property');
        }

        $this->objectData = $objectData;

    }

    /**
     * Returns the uri for this object
     *
     * @return string
     */
    function getName() {

        return $this->objectData['uri'];

    }

    /**
     * Returns the ICalendar-formatted object
     *
     * @return string
     */
    function get() {

        // Pre-populating the 'calendardata' is optional, if we don't have it
        // already we fetch it from the backend.
        if (!isset($this->objectData['calendardata'])) {
            $this->objectData = $this->caldavBackend->getSchedulingObject($this->objectData['principaluri'], $this->objectData['uri']);
        }
        return $this->objectData['calendardata'];

    }

    /**
     * Updates the ICalendar-formatted object
     *
     * @param string|resource $calendarData
     * @return string
     */
    function put($calendarData) {

        throw new MethodNotAllowed('Updating scheduling objects is not supported');

    }

    /**
     * Deletes the scheduling message
     *
     * @return void
     */
    function delete() {

        $this->caldavBackend->deleteSchedulingObject($this->objectData['principaluri'],$this->objectData['uri']);

    }

    /**
     * Returns the mime content-type
     *
     * @return string
     */
    function getContentType() {

        $mime = 'text/calendar; charset=utf-8';
        if (isset($this->objectData['component']) && $this->objectData['component']) {
            $mime.='; component=' . $this->objectData['component'];
        }
        return $mime;

    }

    /**
     * Returns an ETag for this object.
     *
     * The ETag is an arbitrary string, but MUST be surrounded by double-quotes.
     *
     * @return string
     */
    function getETag() {

        if (isset($this->objectData['etag'])) {
            return $this->objectData['etag'];
        } else {
            return '"' . md5($this->get()). '"';
        }

    }

    /**
     * Returns the last modification date as a unix timestamp
     *
     * @return int
     */
    function getLastModified() {

        return $this->objectData['lastmodified'];

    }

    /**
     * Returns the size of this object in bytes
     *
     * @return int
     */
    function getSize() {

        if (array_key_exists('size',$this->objectData)) {
            return $this->objectData['size'];
        } else {
            return strlen($this->get());
        }

    }

    /**
     * Returns the owner principal
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    function getOwner() {

        return $this->objectData['principaluri'];

    }

    /**
     * Returns a group principal
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    function getGroup() {

        return null;

    }

    /**
     * Returns a list of ACE's for this node.
     *
     * Each ACE has the following properties:
     *   * 'privilege', a string such as {DAV:}read or {DAV:}write. These are
     *     currently the only supported privileges
     *   * 'principal', a url to the principal who owns the node
     *   * 'protected' (optional), indicating that this ACE is not allowed to
     *      be updated.
     *
     * @return array
     */
    function getACL() {

        // An alternative acl may be specified in the object data.
        //

        if (isset($this->objectData['acl'])) {
            return $this->objectData['acl'];
        }

        // The default ACL
        return [
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->objectData['principaluri'],
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write',
                'principal' => $this->objectData['principaluri'],
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->objectData['principaluri'] . '/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write',
                'principal' => $this->objectData['principaluri'] . '/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->objectData['principaluri'] . '/calendar-proxy-read',
                'protected' => true,
            ],
        ];

    }

    /**
     * Updates the ACL
     *
     * This method will receive a list of new ACE's.
     *
     * @param array $acl
     * @return void
     */
    function setACL(array $acl) {

        throw new \Sabre\DAV\Exception\MethodNotAllowed('Changing ACL is not yet supported');

    }

    /**
     * Returns the list of supported privileges for this node.
     *
     * The returned data structure is a list of nested privileges.
     * See \Sabre\DAVACL\Plugin::getDefaultSupportedPrivilegeSet for a simple
     * standard structure.
     *
     * If null is returned from this method, the default privilege set is used,
     * which is fine for most common usecases.
     *
     * @return array|null
     */
    function getSupportedPrivilegeSet() {

        return null;

    }

}

