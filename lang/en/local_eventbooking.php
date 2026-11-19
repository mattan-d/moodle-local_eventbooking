<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * English strings.
 *
 * @package    local_eventbooking
 * @copyright  2026 CentricApp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Event registration';
$string['eventbooking:manage'] = 'Manage events and participant reports';
$string['eventbooking:view'] = 'View events';
$string['eventbooking:register'] = 'Register for events';

$string['manageevents'] = 'Manage events';
$string['events'] = 'Events';
$string['newevent'] = 'New event';
$string['editevent'] = 'Edit event';
$string['eventname'] = 'Event name';
$string['description'] = 'Description';
$string['marketing'] = 'Marketing content';
$string['marketing_help'] = 'Shown on the registration page under the description. Formatted text, links and images are supported.';
$string['banner'] = 'Banners';
$string['banner_help'] = 'Images displayed at the top of the event page. The first image is the main banner.';
$string['capacity'] = 'Participant limit for the event';
$string['capacity_help'] = 'Maximum number of confirmed registrations across all dates. 0 means no event-level limit. Each date can have its own limit.';
$string['sessioncapacity'] = 'Participant limit for this date';
$string['sessioncapacity_help'] = 'Maximum registrations for this date. 0 means no limit for the date, still subject to the event limit.';
$string['visible'] = 'Published';
$string['registrationopen'] = 'Registration open';
$string['allowcancel'] = 'Participants can cancel their registration';
$string['closeafterstart'] = 'Close registration once a date has started';
$string['confirmation'] = 'Message after registration';
$string['confirmation_help'] = 'Shown to the participant after a successful registration and included in the confirmation email.';
$string['sessionsheader'] = 'Dates';
$string['sessionnumber'] = 'Date {$a}';
$string['sessionname'] = 'Date name';
$string['timestart'] = 'Starts';
$string['timeend'] = 'Ends';
$string['location'] = 'Location';
$string['addsession'] = 'Add a date';
$string['deletesession'] = 'Remove this date';
$string['saveevent'] = 'Save event';
$string['eventsaved'] = 'Event saved';
$string['eventdeleted'] = 'Event deleted';
$string['deleteevent'] = 'Delete event';
$string['deleteconfirm'] = 'Delete the event "{$a}" and all of its registrations?';
$string['cannotdeletesession'] = 'The date "{$a}" cannot be removed because people are registered for it. Cancel those registrations first.';
$string['endbeforestart'] = 'The end must be after the start.';
$string['invalidcapacity'] = 'The participant limit must be 0 or greater.';
$string['requiresession'] = 'Add at least one date.';
$string['noevents'] = 'There are no events yet.';
$string['noevents_desc'] = 'Create an event, set its dates and participant limit, then send the registration link.';
$string['searchevents'] = 'Search events';
$string['search'] = 'Search';
$string['dates'] = 'Dates';
$string['registered'] = 'Registered';
$string['status'] = 'Status';
$string['actions'] = 'Actions';
$string['published'] = 'Published';
$string['hidden'] = 'Draft';
$string['registrationopenstatus'] = 'Registration open';
$string['registrationclosedstatus'] = 'Registration closed';
$string['view'] = 'View';
$string['edit'] = 'Edit';
$string['report'] = 'Participant report';
$string['sendlink'] = 'Send link';
$string['unlimited'] = 'No limit';
$string['full'] = 'Full';
$string['seatsleft'] = '{$a} places left';
$string['registeredcount'] = '{$a} registered';
$string['catalogue'] = 'Open events';
$string['myregistrations'] = 'My registrations';
$string['nomyregistrations'] = 'You have no registrations.';
$string['register'] = 'Register';
$string['updateregistration'] = 'Update registration';
$string['cancelregistration'] = 'Cancel registration';
$string['registrationsuccess'] = 'You are registered. A confirmation email has been sent.';
$string['registrationupdated'] = 'Your registration details were updated.';
$string['registrationcancelled'] = 'Your registration was cancelled.';
$string['cancelnotallowed'] = 'This registration cannot be cancelled.';
$string['registrationclosed'] = 'Registration for this event is closed.';
$string['eventfull'] = 'Every date is full.';
$string['eventnotavailable'] = 'This event is not available.';
$string['eventnotfound'] = 'Event not found.';
$string['choosesession'] = 'Choose a date';
$string['sessionfull'] = 'This date is full.';
$string['sessionclosed'] = 'Closed';
$string['alreadyregistered'] = 'You are registered for: {$a}';
$string['changehint'] = 'You can update your details or move to another date if places remain.';
$string['personaldetails'] = 'Personal details';
$string['organisationdetails'] = 'Organisation details';
$string['phone'] = 'Phone';
$string['institution'] = 'Organisation';
$string['department'] = 'Department';
$string['city'] = 'City';
$string['jobtitle'] = 'Job title';
$string['notes'] = 'Notes';
$string['placeholderfirstname'] = 'e.g. Dana';
$string['placeholderlastname'] = 'e.g. Cohen';
$string['placeholderemail'] = 'name@example.com';
$string['placeholderphone'] = '050-0000000';
$string['placeholderidnumber'] = 'ID number';
$string['placeholderinstitution'] = 'Organisation name';
$string['placeholderdepartment'] = 'Department name';
$string['placeholderjobtitle'] = 'e.g. Training coordinator';
$string['placeholdercity'] = 'e.g. Haifa';
$string['placeholdernotes'] = 'Anything we should know before the session';
$string['requiredsession'] = 'Choose a date.';
$string['confirmed'] = 'Confirmed';
$string['cancelled'] = 'Cancelled';
$string['allsessions'] = 'All dates';
$string['allstatuses'] = 'All statuses';
$string['downloadreport'] = 'Download report';
$string['participants'] = 'Participants';
$string['noregistrations'] = 'No participants to show.';
$string['registeredon'] = 'Registered on';
$string['sendlinkheading'] = 'Send the registration link';
$string['linkhint'] = 'Copy the link or email it to users. It opens this event\'s registration page.';
$string['copylink'] = 'Copy link';
$string['linkcopied'] = 'Link copied';
$string['recipients'] = 'Recipients';
$string['nousersselected'] = 'Choose users';
$string['personalmessage'] = 'Personal message';
$string['personalmessage_help'] = 'Added to the email above the registration link.';
$string['send'] = 'Send';
$string['linksent'] = 'The link was sent to {$a} users.';
$string['linksentpartial'] = 'The link was sent to {$a->sent} users. {$a->skipped} recipients were skipped.';
$string['norecipients'] = 'Choose at least one user.';
$string['recentsends'] = 'Recent sends';
$string['sentto'] = 'Recipient';
$string['sentby'] = 'Sent by';
$string['timesent'] = 'Sent at';
$string['nosends'] = 'The link has not been sent yet.';
$string['notvisiblewarning'] = 'This event is still a draft. Users cannot open the link until it is published.';
$string['registrationnotopenwarning'] = 'Registration is closed. Users can open the page but cannot register.';
$string['emailsubject'] = 'Invitation to register: {$a}';
$string['emailbody'] = 'Hello {$a->fullname},

You are invited to register for {$a->eventname}.

{$a->message}

View the dates and register:
{$a->link}

{$a->sitename}';
$string['confirmationsubject'] = 'Registration confirmed: {$a}';
$string['confirmationbody'] = 'Hello {$a->fullname},

You are registered for {$a->eventname}.
Date: {$a->session}

{$a->confirmation}

Event page:
{$a->link}

{$a->sitename}';
$string['updatesubject'] = 'Registration updated: {$a}';
$string['statsevents'] = 'Events';
$string['statsopen'] = 'Open for registration';
$string['statsregistrations'] = 'Active registrations';
$string['backtoevents'] = 'Back to events';
$string['past'] = 'Finished';
$string['upcoming'] = 'Upcoming';
$string['nodates'] = 'No date set';
$string['sessioncount'] = '{$a} dates';
$string['lockfailed'] = 'Could not save the registration. Please try again.';
$string['cannotregister'] = 'You cannot register for events.';
$string['invalidemail'] = 'Enter a valid email address.';
$string['missinguser'] = 'That user cannot receive email.';
$string['reportsummary'] = '{$a->confirmed} confirmed, {$a->cancelled} cancelled';
$string['availability'] = 'Registration settings';
$string['general'] = 'Event content';
$string['filter'] = 'Filter';
$string['reset'] = 'Reset';
$string['occupancy'] = 'Places';
$string['eventlink'] = 'Registration link';
$string['privacy:metadata:local_eventbooking_reg'] = 'Personal and organisational details submitted when registering for an event.';
$string['privacy:metadata:local_eventbooking_reg:userid'] = 'The user who registered.';
$string['privacy:metadata:local_eventbooking_reg:firstname'] = 'First name submitted with the registration.';
$string['privacy:metadata:local_eventbooking_reg:lastname'] = 'Last name submitted with the registration.';
$string['privacy:metadata:local_eventbooking_reg:email'] = 'Email submitted with the registration.';
$string['privacy:metadata:local_eventbooking_reg:phone'] = 'Phone number submitted with the registration.';
$string['privacy:metadata:local_eventbooking_reg:idnumber'] = 'ID number submitted with the registration.';
$string['privacy:metadata:local_eventbooking_reg:institution'] = 'Organisation submitted with the registration.';
$string['privacy:metadata:local_eventbooking_reg:department'] = 'Department submitted with the registration.';
$string['privacy:metadata:local_eventbooking_reg:city'] = 'City submitted with the registration.';
$string['privacy:metadata:local_eventbooking_reg:jobtitle'] = 'Job title submitted with the registration.';
$string['privacy:metadata:local_eventbooking_reg:notes'] = 'Notes submitted with the registration.';
$string['privacy:metadata:local_eventbooking_reg:status'] = 'Whether the registration is confirmed or cancelled.';
$string['privacy:metadata:local_eventbooking_reg:timecreated'] = 'When the registration was created.';
$string['privacy:metadata:local_eventbooking_sent'] = 'Record of registration links emailed to a user.';
$string['privacy:metadata:local_eventbooking_sent:userid'] = 'The user who received the link.';
$string['privacy:metadata:local_eventbooking_sent:email'] = 'Email address the link was sent to.';
$string['privacy:metadata:local_eventbooking_sent:sentby'] = 'The user who sent the link.';
$string['privacy:metadata:local_eventbooking_sent:message'] = 'Optional message included with the link.';
$string['privacy:metadata:local_eventbooking_sent:timecreated'] = 'When the link was sent.';
