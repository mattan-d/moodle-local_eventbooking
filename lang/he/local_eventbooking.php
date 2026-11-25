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
 * Hebrew strings.
 *
 * @package    local_eventbooking
 * @copyright  2026 CentricApp
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'הרשמה לאירועים';
$string['eventbooking:manage'] = 'ניהול אירועים ודוחות משתתפים';
$string['eventbooking:view'] = 'צפייה באירועים';
$string['eventbooking:register'] = 'הרשמה לאירועים';

$string['manageevents'] = 'ניהול אירועים';
$string['events'] = 'אירועים';
$string['newevent'] = 'אירוע חדש';
$string['editevent'] = 'עריכת אירוע';
$string['eventname'] = 'שם האירוע';
$string['description'] = 'תיאור';
$string['marketing'] = 'תוכן שיווקי';
$string['marketing_help'] = 'מוצג בעמוד ההרשמה מתחת לתיאור. אפשר לכלול טקסט מעוצב, קישורים ותמונות.';
$string['banner'] = 'באנרים';
$string['banner_help'] = 'תמונות שיוצגו בראש עמוד האירוע. התמונה הראשונה היא הבאנר הראשי.';
$string['capacity'] = 'מגבלת משתתפים לאירוע';
$string['capacity_help'] = 'מספר הנרשמים המאושרים המרבי בכל המועדים יחד. 0 משמעו ללא מגבלה ברמת האירוע. לכל מועד אפשר להגדיר מגבלה נפרדת.';
$string['sessioncapacity'] = 'מגבלת משתתפים למועד';
$string['sessioncapacity_help'] = 'מספר הנרשמים המרבי למועד זה. 0 משמעו ללא מגבלה למועד, בכפוף למגבלת האירוע.';
$string['visible'] = 'מפורסם';
$string['registrationopen'] = 'הרשמה פתוחה';
$string['allowcancel'] = 'משתתפים יכולים לבטל הרשמה';
$string['closeafterstart'] = 'סגירת הרשמה למועד שהתחיל';
$string['confirmation'] = 'הודעה לאחר הרשמה';
$string['confirmation_help'] = 'מוצגת למשתתף אחרי הרשמה מוצלחת ונכללת בדוא"ל האישור.';
$string['sessionsheader'] = 'מועדים';
$string['sessionnumber'] = 'מועד {$a}';
$string['sessionname'] = 'שם המועד';
$string['timestart'] = 'התחלה';
$string['timeend'] = 'סיום';
$string['location'] = 'מיקום';
$string['addsession'] = 'הוספת מועד';
$string['deletesession'] = 'הסרת מועד זה';
$string['saveevent'] = 'שמירת אירוע';
$string['eventsaved'] = 'האירוע נשמר';
$string['eventdeleted'] = 'האירוע נמחק';
$string['deleteevent'] = 'מחיקת אירוע';
$string['deleteconfirm'] = 'למחוק את האירוע "{$a}" ואת כל ההרשמות אליו?';
$string['cannotdeletesession'] = 'אי אפשר להסיר את המועד "{$a}" כי יש אליו נרשמים. יש לבטל את ההרשמות קודם.';
$string['endbeforestart'] = 'מועד הסיום חייב להיות אחרי מועד ההתחלה.';
$string['invalidcapacity'] = 'מגבלת המשתתפים חייבת להיות 0 או יותר.';
$string['requiresession'] = 'יש להגדיר לפחות מועד אחד.';
$string['noevents'] = 'עדיין אין אירועים.';
$string['noevents_desc'] = 'צרו אירוע, הגדירו מועדים ומגבלת משתתפים, ושלחו את קישור ההרשמה.';
$string['searchevents'] = 'חיפוש אירועים';
$string['search'] = 'חיפוש';
$string['dates'] = 'מועדים';
$string['registered'] = 'נרשמים';
$string['status'] = 'סטטוס';
$string['actions'] = 'פעולות';
$string['published'] = 'מפורסם';
$string['hidden'] = 'טיוטה';
$string['registrationopenstatus'] = 'הרשמה פתוחה';
$string['registrationclosedstatus'] = 'הרשמה סגורה';
$string['dateuntil'] = 'עד {$a}';
$string['view'] = 'צפייה';
$string['userview'] = 'תצוגת משתמש';
$string['previewback'] = 'חזרה לניהול';
$string['previewnosave'] = 'זו תצוגה בלבד. ההרשמה לא נשמרה.';
$string['previewhidden'] = 'האירוע עדיין מוסתר ממשתמשים.';
$string['edit'] = 'עריכה';
$string['report'] = 'דוח משתתפים';
$string['sendlink'] = 'שליחת קישור';
$string['unlimited'] = 'ללא מגבלה';
$string['full'] = 'מלא';
$string['seatsleft'] = 'נותרו {$a} מקומות';
$string['registeredcount'] = '{$a} נרשמים';
$string['catalogue'] = 'אירועים פתוחים';
$string['myregistrations'] = 'ההרשמות שלי';
$string['nomyregistrations'] = 'אין לך הרשמות.';
$string['register'] = 'הרשמה';
$string['updateregistration'] = 'עדכון הרשמה';
$string['cancelregistration'] = 'ביטול הרשמה';
$string['registrationsuccess'] = 'ההרשמה נשמרה. נשלח אליך דוא"ל אישור.';
$string['registrationupdated'] = 'פרטי ההרשמה עודכנו.';
$string['registrationcancelled'] = 'ההרשמה בוטלה.';
$string['cancelnotallowed'] = 'לא ניתן לבטל הרשמה זו.';
$string['registrationclosed'] = 'ההרשמה לאירוע זה סגורה.';
$string['eventfull'] = 'כל המועדים מלאים.';
$string['eventnotavailable'] = 'האירוע אינו זמין.';
$string['eventnotfound'] = 'האירוע לא נמצא.';
$string['choosesession'] = 'בחירת מועד';
$string['sessionfull'] = 'המועד מלא.';
$string['sessionclosed'] = 'סגור';
$string['alreadyregistered'] = 'אתם רשומים למועד: {$a}';
$string['changehint'] = 'אפשר לעדכן את מספר הטלפון או לעבור למועד אחר, אם נותרו מקומות.';
$string['personaldetails'] = 'פרטים אישיים';
$string['profilelocked'] = 'השם, מספר הזיהוי והדוא״ל נלקחים מהחשבון. אפשר לשנות רק את הטלפון.';
$string['organisationdetails'] = 'פרטים ארגוניים';
$string['phone'] = 'טלפון';
$string['institution'] = 'ארגון';
$string['department'] = 'מחלקה';
$string['city'] = 'יישוב';
$string['jobtitle'] = 'תפקיד';
$string['notes'] = 'הערות';
$string['placeholderfirstname'] = 'לדוגמה: דנה';
$string['placeholderlastname'] = 'לדוגמה: כהן';
$string['placeholderemail'] = 'name@example.com';
$string['placeholderphone'] = '050-0000000';
$string['placeholderidnumber'] = 'מספר זהות';
$string['placeholderinstitution'] = 'שם הארגון';
$string['placeholderdepartment'] = 'שם המחלקה';
$string['placeholderjobtitle'] = 'לדוגמה: רכזת הדרכה';
$string['placeholdercity'] = 'לדוגמה: חיפה';
$string['placeholdernotes'] = 'משהו שחשוב שנדע לפני המפגש';
$string['requiredsession'] = 'יש לבחור מועד.';
$string['confirmed'] = 'מאושר';
$string['cancelled'] = 'בוטל';
$string['allsessions'] = 'כל המועדים';
$string['allstatuses'] = 'כל הסטטוסים';
$string['downloadreport'] = 'הורדת דוח';
$string['participants'] = 'משתתפים';
$string['noregistrations'] = 'אין נרשמים להצגה.';
$string['registeredon'] = 'תאריך הרשמה';
$string['sendlinkheading'] = 'שליחת קישור להרשמה';
$string['linkhint'] = 'העתיקו את הקישור או שלחו אותו בדוא"ל למשתמשים. הקישור פותח את עמוד ההרשמה של האירוע.';
$string['copylink'] = 'העתקת קישור';
$string['linkcopied'] = 'הקישור הועתק';
$string['recipients'] = 'נמענים';
$string['nousersselected'] = 'בחרו משתמשים';
$string['personalmessage'] = 'הודעה אישית';
$string['personalmessage_help'] = 'תתווסף לדוא"ל מעל קישור ההרשמה.';
$string['send'] = 'שליחה';
$string['linksent'] = 'הקישור נשלח אל {$a} משתמשים.';
$string['linksentpartial'] = 'הקישור נשלח אל {$a->sent} משתמשים. {$a->skipped} נמענים דולגו.';
$string['norecipients'] = 'בחרו לפחות משתמש אחד.';
$string['recentsends'] = 'שליחות אחרונות';
$string['sentto'] = 'נמען';
$string['sentby'] = 'נשלח על ידי';
$string['timesent'] = 'מועד שליחה';
$string['nosends'] = 'הקישור עדיין לא נשלח.';
$string['notvisiblewarning'] = 'האירוע עדיין בטיוטה. משתמשים לא יוכלו לפתוח את הקישור עד שהאירוע יפורסם.';
$string['registrationnotopenwarning'] = 'ההרשמה סגורה. המשתמשים יוכלו לפתוח את העמוד אבל לא להירשם.';
$string['emailsubject'] = 'הזמנה להרשמה: {$a}';
$string['emailbody'] = 'שלום {$a->fullname},

הוזמנת להירשם לאירוע {$a->eventname}.

{$a->message}

לצפייה במועדים ולהרשמה:
{$a->link}

{$a->sitename}';
$string['confirmationsubject'] = 'אישור הרשמה: {$a}';
$string['confirmationbody'] = 'שלום {$a->fullname},

נרשמת לאירוע {$a->eventname}.
מועד: {$a->session}

{$a->confirmation}

עמוד האירוע:
{$a->link}

{$a->sitename}';
$string['updatesubject'] = 'ההרשמה עודכנה: {$a}';
$string['statsevents'] = 'אירועים';
$string['statsopen'] = 'פתוחים להרשמה';
$string['statsregistrations'] = 'נרשמים פעילים';
$string['backtoevents'] = 'חזרה לאירועים';
$string['past'] = 'הסתיים';
$string['upcoming'] = 'קרוב';
$string['nodates'] = 'לא נקבע מועד';
$string['sessioncount'] = '{$a} מועדים';
$string['lockfailed'] = 'לא ניתן לשמור את ההרשמה. נסו שוב.';
$string['cannotregister'] = 'אין הרשאה להירשם לאירועים.';
$string['invalidemail'] = 'יש להזין כתובת דוא"ל תקינה.';
$string['missinguser'] = 'לא ניתן לשלוח דוא"ל למשתמש זה.';
$string['reportsummary'] = '{$a->confirmed} מאושרים, {$a->cancelled} ביטולים';
$string['availability'] = 'הגדרות הרשמה';
$string['general'] = 'תוכן האירוע';
$string['filter'] = 'סינון';
$string['reset'] = 'איפוס';
$string['occupancy'] = 'מקומות';
$string['eventlink'] = 'קישור להרשמה';
$string['privacy:metadata:local_eventbooking_reg'] = 'פרטים אישיים וארגוניים שנמסרו בעת הרשמה לאירוע.';
$string['privacy:metadata:local_eventbooking_reg:userid'] = 'המשתמש שנרשם.';
$string['privacy:metadata:local_eventbooking_reg:firstname'] = 'השם הפרטי שנמסר בהרשמה.';
$string['privacy:metadata:local_eventbooking_reg:lastname'] = 'שם המשפחה שנמסר בהרשמה.';
$string['privacy:metadata:local_eventbooking_reg:email'] = 'כתובת הדוא"ל שנמסרה בהרשמה.';
$string['privacy:metadata:local_eventbooking_reg:phone'] = 'מספר הטלפון שנמסר בהרשמה.';
$string['privacy:metadata:local_eventbooking_reg:idnumber'] = 'מספר הזיהוי שנמסר בהרשמה.';
$string['privacy:metadata:local_eventbooking_reg:institution'] = 'הארגון שנמסר בהרשמה.';
$string['privacy:metadata:local_eventbooking_reg:department'] = 'המחלקה שנמסרה בהרשמה.';
$string['privacy:metadata:local_eventbooking_reg:city'] = 'היישוב שנמסר בהרשמה.';
$string['privacy:metadata:local_eventbooking_reg:jobtitle'] = 'התפקיד שנמסר בהרשמה.';
$string['privacy:metadata:local_eventbooking_reg:notes'] = 'הערות שנמסרו בהרשמה.';
$string['privacy:metadata:local_eventbooking_reg:status'] = 'אם ההרשמה מאושרת או בוטלה.';
$string['privacy:metadata:local_eventbooking_reg:timecreated'] = 'מועד יצירת ההרשמה.';
$string['privacy:metadata:local_eventbooking_sent'] = 'תיעוד של קישורי הרשמה שנשלחו למשתמש בדוא"ל.';
$string['privacy:metadata:local_eventbooking_sent:userid'] = 'המשתמש שקיבל את הקישור.';
$string['privacy:metadata:local_eventbooking_sent:email'] = 'כתובת הדוא"ל שאליה נשלח הקישור.';
$string['privacy:metadata:local_eventbooking_sent:sentby'] = 'המשתמש ששלח את הקישור.';
$string['privacy:metadata:local_eventbooking_sent:message'] = 'הודעה אופציונלית שצורפה לקישור.';
$string['privacy:metadata:local_eventbooking_sent:timecreated'] = 'מועד שליחת הקישור.';
