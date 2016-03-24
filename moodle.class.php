<?php

class Moodle
{
	//protected 
	public $_mysqli;
	//protected 
	public $_mssql;

	protected $_userIdPrefix;
	protected $_userInstitution;

/*CONTEXT_SYSTEM = 10;
_USER = 30;
_COURSECAT = 40;
_COURSE = 50;
_MODULE = 70;
_BLOCK = 80;
*/

	public $sessionStartDate    = '30.05.2016';
    public $previousSessionDate = '18.01.2016';


	var $categoriesArray = array( 2  => 5624,
                                  3  => 5601,
                                  4  => 3075,
                                  6  => 4027,
                                  7  => 5275,
                                  8  => 663,
                                  20 => 7971,
                                  21 => 7790,
                                  35 => 4090,
                                  38 => 6285,
                                  39 => 7496,
                                  40 => 7463,
                                  41 => 7462,
                                  44 => 5749,
                                  45 => 7423,
                                  55 => 6782,
                                  56 => 6126,
                                  58 => 2759,
                                  65 => 8211,
                                  69 => 7984,
                                  70 => 7465,
                                  71 => 7839,
                                  76 => 8340,
                                  77 => 8804,
                                  78 => 9774,
                                  79 => 9684,
                                  80 => 9927);

    // менеджмент СО - планы
    public $managementSO = array(272,294,295,297,298,299,300,301,302);
    public $managementSOCourse = array('1' => 92, '2' => 93, '3' => 94, '4' => 99, '5' => 258);

    // менеджмент СПО - планы
    public $managementSPO = array(273,274,276,277,284,287,290,303,304);
    public $managementSPOCourse = array('1' => 177, '2' => 176, '3' => 201, '4' => 236);



	public function __construct($userIdPrefix = '', $userInstitution = '') {
		global $CFG;
		$this->_userIdPrefix = $userIdPrefix;
		$this->_userInstitution = $userInstitution;

		$this->_mysqli = new mysqliWrapper('edu_');

        $this->_mssql = new mssqlWrapper();
        return true;
	}

	public function __destruct() {
		$this->_mysqli->close();
		unset($this->_mysqli);
		return true;
	}

	public function convertUserId($userId) {
		return substr($userId, 3);
		// TODO: переделать. сделать проверку
	}

	public function getAllUsers($format = 'moodle', $conditions = null) {
		$users = array();
		$query = "SELECT * FROM edu_user WHERE idnumber LIKE '".$this->_userIdPrefix."%'";

		$queryResult = $this->_mysqli->query($query);
		while ($result = $queryResult->fetch_assoc()) {
			$result['fullname'] = $result['lastname']." ".$result['firstname'];

			if (empty($result['middlename']) === false) {
				$result['fullname'] .= " ".$result['middlename'];
			}

			switch ($format) {
				case 'moodle':
					$users[$result['id']] = $result;
					break;

				case 'base':
					$users[$this->convertUserId($result['idnumber'])] = $result;
					break;
			}
		}

		return $users;
	}
/*
	public function syncUserEmails() {
		$query = "SELECT * FROM edu_user WHERE idnumber LIKE '".$this->_userIdPrefix."%'";

		$queryResult = $this->_mysqli->query($query);
		while ($result = $queryResult->fetch_assoc()) {
			$userId = $this->convertUserId($result['idnumber']);
			$queryText = "SELECT e_mail FROM student WHERE id=".$userId."";

			$queryResult2 = mssql\_query($queryText, $this->_mssql);
			$result2 = mssql\_fetch_assoc($queryResult2);

			echo $result['id']." ".$result['username']." ";

			if (is_null($result2['e_mail']) === false) {
				$queryEmail = "SELECT id FROM edu_user WHERE email LIKE '%".$result2['e_mail']."%' AND id !='".$result['id']."'";
				$queryResult3 = $this->_mysqli->query($queryEmail);

				if ($queryResult3->num_rows > 0) {
					echo "<i>!!!</i>";
					echo $result2['e_mail'];

				} else if (strcmp($result['username'], $result2['e_mail']) !== 0) {

					//$queryUpdate = "UPDATE edu_user SET username='".$result2['e_mail']."', email='".$result2['e_mail']."' WHERE id=".$result['id'];
					//if ($this->_mysqli->query($queryUpdate) === true) {
					//	echo "ok!";
					//} else {
						echo $result2['e_mail']."<b>!</b>";
					//}
				}
			} else {
				echo "нет адреса электронной почты";
			}
			echo "<br>";
		}
		return true;
	}
*/
    public function getContext($instanceId, $contextLevel = 50) {
    	$context = $this->_mysqli->getRecord('context', array('contextlevel' => $contextLevel, 'instanceid' => $instanceId), 'id');
    	return $context['id'];
    }


    public function createContext($instanceId, $contextLevel = 50, $parent = 1) {
    	$parentContext = $this->_mysqli->getRecord('context', array('id' => $parent), array('depth', 'path'));

    	if ($parentContext === false) {
    		return false;
    	}

    	$contextId = $this->_mysqli->insertRecord('context', array('contextlevel' => $contextLevel, 'instanceid' => $instanceId, 'depth' => 0, 'path' => ''));
    	$this->_mysqli->updateRecord('context', array('depth' => ($parentContext['depth'] + 1), 'path' => $parentContext['path'].'/'.$contextId), array('id' => $contextId));
    	return $contextId;
    }

    public function setUserPreferences($userId, $name, $value) {
    	return $this->_mysqli->insertRecord('user_preferences', array('userid' => $userId, 'name' => $name, 'value' => $value));
    }

	protected function _createUser($user) {

		if ($user['password'] == 0) {
			$user['password'] = 'to be generated';

			if (strpos($user['email'], 'nomail.no') === false) {
				$hasEmail = true;
			}
		}

		$userDefaults = array('mnethostid' => 1, 'lang' => 'ru', 'timezone' => 99, 'mailformat' => 1,
			                  'maildisplay' => 0, 'maildigest' => 0, 'autosubscribe' => 0,
			                  'institution' => $this->_userInstitution, 'department' => '',
				          	  'phone1' => '', 'phone2' => '', 'address' => '', 'url' => '',
				          	  'description' => '', 'descriptionformat' => 1, 'auth' => 'manual',
				          	  'confirmed' => 1, 'suspended' => 0,
				          	  'calendartype' => 'gregorian', 'trackforums' => 0);

		$userDefaults['timemodified'] = time();
		$userDefaults['timecreated']  = $userDefaults['timemodified'];

		$user = $user + $userDefaults;

		$newUserId = $this->_mysqli->insertRecord('user', $user);

		if ($newUserId > 0) {
			if ($hasEmail === true) {
				$this->setUserPreferences($newUserId, 'create_password', 1);
			}
			$this->createContext($newUserId, 30);
		}

		return $newUserId;
	}

	// поиск населенного пункта в адресе.
	public function getLocality($address) {
		$locality = '';

		$arr = explode(',', $address);

    	foreach ($arr as $v) {
    		$locality .= $v;

    		if (preg_match("/(г|п|с|пос|ст|дер|д|с\.п|х)\./is", $v) === 1) {
        		break;
        	}
        	$locality .= ', ';
    	}

    	return $locality;
	}


	// поиск курса по идентификатору
	public function getCourseByIdnumber($idNumber) {
        $course = explode('-', $idNumber);
        if (in_array($course[0], $this->managementSO) === true && 
        	$course[1] <= sizeof($this->managementSOCourse)) {
            return $this->managementSOCourse[$course[1]];
        }

        if (in_array($course[0], $this->managementSPO) === true && 
        	$course[1] <= sizeof($this->managementSPOCourse)) {
            return $this->managementSPOCourse[$course[1]];
        }

        return $this->_mysqli->getRecordId("course", array('idnumber' => $idNumber), 'id');
    }

    // поиск пользователя по идентификатору
    public function getUserByIdnumber($idnumber) {
    	return $this->_mysqli->getRecordId("user", array('idnumber' => $idnumber), 'id');
    }

    // поиск пользователя по адресу электронной почты
    public function getUserByEmail($email) {
    	return $this->_mysqli->getRecordId("user", array('email' => $email), 'id');
    }

    // проверить записан ли пользователь на курс
    // user/enrol/get
    public function userGetEnrolments($enrolId, $userId) {
    	return $this->_mysqli->getRecordId("user_enrolments", array('enrolid' => $enrolId, 'userid' => $userId), 'id');
    }

	// проверить записан ли пользователь на роль на курсе
	// user/enrol/getassignments
    public function userGetEnrolAssignments($contextId, $userId, $roleId = 5) {
        return $this->_mysqli->getRecordId("role_assignments", array('roleid' => $roleId, 'userid' => $userId, 'contextid' => $contextId, 'component' => '', 'itemid' => 0), 'id');
    }

    // поиск идентификатора роли
    // course/enrol/findId
    public function findEnrolId($courseId, $roleId='5', $enrol = 'manual') {
        return $this->_mysqli->getRecordId("enrol", array('courseid' => $courseId, 'enrol' => $enrol, 'roleid' => $roleId), 'id');
    }

    // course/enrol/getInfo
    public function enrolGetInfo($enrolId) {
    	return $this->_mysqli->getRecord("enrol", array('id' => $enrolId), array('courseid', 'roleid', 'enrol'));
    }

    // все роли пользователя
    // user/getEnrolments
    public function userGetEnrolments2($userId) {
    	return $this->_mysqli->getRecords('user_enrolments', array('userid' => $userId), 'enrolid');
    }

    // user/assign
    public function assignUser($userId, $courseId, $roleId = 5) {
        $enrolId = $this->findEnrolId($courseId, $roleId);
        $contextId = $this->getContext($courseId, CONTEXT_COURSE);

        try {
            $this->_mysqli->autocommit(FALSE); // start transaction

            $time = time();

            if ($this->userGetEnrolments($enrolId, $userId) > 0) {
                echo "<b>Пользователь (".$userId.") уже зачислен на курс (".$courseId.")</b>";
            } else {
                $createEnrolQuery = "INSERT INTO edu_user_enrolments (enrolid,status,userid,timestart,timeend,modifierid,timecreated,timemodified)
                                     VALUES('".$enrolId."','0','".$userId."','".$time."','0','2','".$time."','".$time."')";

                if ($this->_mysqli->query($createEnrolQuery) === false) {
                    throw new Exception($this->_mysqli->error);
                }
            }

            if ($this->userGetEnrolAssignments($contextId, $userId, $roleId) > 0) {
                echo "<b> и назначен на роль (".$roleId.")</b><br>";
            } else {
            	if ($this->_mysqli->insertRecord('role_assignments', array('roleid' => $roleId, 'contextid' => $contextId, 
            		'userid' => $userId, 'component' => '', 'itemid' => 0, 'timemodified' => time(), 'modifierid' => 2, 'sortorder' => 0)) === false) {
                    throw new Exception($this->_mysqli->error);
                }
            }

            $this->_mysqli->commit();
            $this->_mysqli->autocommit(TRUE); // end transaction
        }
        catch ( Exception $e ) {
            echo "Ошибка (пользователь ".$userId."): ".$e->getMessage()."<br>";
            $this->_mysqli->rollback();
            $this->_mysqli->autocommit(TRUE); // end transaction
            return false;
        }
        return true;
    }

    // user/unassign
    public function unassignUser($userId, $courseId, $roleId = 5) {
    	$enrolId = $this->findEnrolId($courseId, $roleId);
        $contextId = $this->getContext($courseId, CONTEXT_COURSE);

        $enrolmentId = $this->userGetEnrolments($enrolId, $userId);

    	if ($enrolmentId === false) {
    		echo "пользователь уже не на курсе!";
    		return false;
    	}

        $roleAssignent = $this->userGetEnrolAssignments($contextId, $userId);
        $this->_mysqli->deleteRecord('role_assignments', ['id' => $roleAssignent]);
/*
SELECT * FROM edu_cache_flags WHERE name = '/1/3695/2852/4282/3967' AND flagtype = 'accesslib/dirtycontexts' LIMIT 0, 1
INSERT INTO edu_cache_flags (flagtype,name,value,expiry,timemodified) VALUES('accesslib/dirtycontexts','/1/3695/2852/4282/3967','1','1456932122','1456917722')
SELECT ctx.*\
                  FROM edu_context ctx\
                 WHERE ctx.path LIKE '/1/3695/2852/4282/3967/%'
SELECT * FROM edu_role_assignments WHERE userid = '324' AND contextid = '12037'

*/

    	// удалить Роль
    	// проверить группы:
    	/* SELECT gm.id as gmid, gm.userid, g.*\
              FROM edu_groups_members gm\
        INNER JOIN edu_groups g\
                ON gm.groupid = g.id\
             WHERE g.courseid = '126' AND gm.userid = '324'
		*/
	/*
	SELECT * FROM edu_events_handlers WHERE eventname = 'groups_members_removed'
SELECT * FROM edu_grade_items WHERE  courseid = '126'
SELECT * FROM edu_grade_grades WHERE  userid = '324' AND itemid = '1125'
DELETE FROM edu_grade_grades WHERE id = '59971'
INSERT INTO edu_grade_grades_history (itemid,userid,rawgrade,rawgrademax,rawgrademin,rawscaleid,usermodified,finalgrade,hidden,locked,locktime,exported,overridden,excluded,timemodified,feedback,feedbackformat,information,informationformat,action,oldid,source,loggeduser) VALUES('1125','324',NULL,'100.00000','0.00000',NULL,NULL,'94.37500','0','0','0','0','0','0','1456917722',NULL,'0',NULL,'0','3','59971','userdelete','2')
	 */
	
	/*
	
SELECT * FROM edu_events_handlers WHERE eventname = 'user_unenrolled'

	 */
	


	/*
	
// instance->id = enrolid!

require_once("$CFG->dirroot/group/lib.php");

        
*/
        // Remove all users groups linked to this enrolment instance.
        if ($gms = $this->_mysqli->getRecords('groups_members', array('userid'=>$userId, 'component'=>'enrol_manual', 'itemid'=>$enrolId))) {
            foreach ($gms as $gm) {
            	$this->groupUnassign($userId, $gm['groupid']);
            }
        }
        /*
        role_unassign_all(array('userid'=>$userid, 'contextid'=>$context->id, 'component'=>'enrol_'.$name, 'itemid'=>$instance->id));
        */
        $this->_mysqli->deleteRecord('user_enrolments', array('id' => $enrolmentId));
        //$DB->delete_records('user_enrolments', array('id'=>$ue->id));
        //
        /*
        DELETE FROM edu_user_lastaccess WHERE userid = '324' AND courseid = '126'
DELETE FROM edu_forum_digests WHERE userid = '324' AND forum IN (SELECT f.id FROM edu_forum f WHERE f.course = '126')
DELETE FROM edu_forum_subscriptions WHERE userid = '324' AND forum IN (SELECT f.id FROM edu_forum f WHERE f.course = '126')
DELETE FROM edu_forum_track_prefs WHERE userid = '324' AND forumid IN (SELECT f.id FROM edu_forum f WHERE f.course = '126')
DELETE FROM edu_forum_read WHERE userid = '324' AND forumid IN (SELECT f.id FROM edu_forum f WHERE f.course = '126')
*/
		$this->_mysqli->deleteRecord('user_lastaccess', ['userid' => $userId, 'courseid' => $courseId]);


        /*
        // add extra info and trigger event
        $ue->courseid  = $courseid;
        $ue->enrol     = $name;

        $sql = "SELECT 'x'
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON (e.id = ue.enrolid)
                 WHERE ue.userid = :userid AND e.courseid = :courseid";
        if ($DB->record_exists_sql($sql, array('userid'=>$userid, 'courseid'=>$courseid))) {
            $ue->lastenrol = false;

        } else {
            // the big cleanup IS necessary!
            require_once("$CFG->libdir/gradelib.php");

            // remove all remaining roles
            role_unassign_all(array('userid'=>$userid, 'contextid'=>$context->id), true, false);

            //clean up ALL invisible user data from course if this is the last enrolment - groups, grades, etc.
            groups_delete_group_members($courseid, $userid);

            grade_user_unenrol($courseid, $userid);

            $DB->delete_records('user_lastaccess', array('userid'=>$userid, 'courseid'=>$courseid));

            $ue->lastenrol = true; // means user not enrolled any more
        }
        // Trigger event.
        $event = \core\event\user_enrolment_deleted::create(
                array(
                    'courseid' => $courseid,
                    'context' => $context,
                    'relateduserid' => $ue->userid,
                    'objectid' => $ue->id,
                    'other' => array(
                        'userenrolment' => (array)$ue,
                        'enrol' => $name
                        )
                    )
                );
        $event->trigger();
        // reset all enrol caches
        $context->mark_dirty();
	 */
		$this->courseCacheUpdate($courseId);
		return true;
    }

    public function userLastAccess($userId, $courseId) {
    	return $this->_mysqli->getRecordId('user_lastaccess', ['userid' => $userId, 'courseid' => $courseId]);
    }

    public function userGeneratePasswordHash($password) {
        global $CFG;
        require_once($CFG->dirroot . '/lib/password_compat/lib/password.php');

        return password_hash( $password, PASSWORD_DEFAULT, array() );
    }

	public function syncUsers() {
		$users = $this->getAllUsers('base');
		$query = 'SELECT * FROM student WHERE id NOT IN ('.implode(',', array_keys($users)).') AND catalog >= 419 AND catalog <= 426';

		$queryResult = $this->_mssql->_query($query);

		while ($result = $queryResult->fetch(PDO::FETCH_ASSOC)) {
			if (is_null($result['e_mail']) === true) {
				$result['e_mail'] = $this->_userIdPrefix.$result['id']."@nomail.no";
				$hasEmail = false;
			} else {
				$hasEmail = true;
			}

			echo $result['id']." ".$result['surname']." ".$result['name']." ".$result['e_mail']."<br>";

			$user = array('lastname' => $result['surname'], 'firstname' => $result['name'], 'middlename' => $result['second_name'],
				          'email' => $result['e_mail'], 'username' => $result['e_mail'], 'idnumber' => $this->_userIdPrefix.$result['id'],
				          'city' => $this->getLocality($result['address']), 'country' => 'RU');

			if ($result['region'] == 185) {
				$user['country'] = 'AZ';
			}

			$this->_createUser($user, $hasEmail);
		}
	}

	// user/get
	public function getUser($userId) {
		$user = $this->_mysqli->getRecord('user', array('id' => $userId));

		$user['fullname'] = $user['lastname']." ".$user['firstname'];
		if (empty($user['middlename']) === false) {
			$user['fullname'] .= " ".$user['middlename'];
		}

		return $user;
	}

	// user/getBase
	public function userGetBase($userId) {
		$idNumber = $this->getUser($userId)['idnumber'];

		return $this->_mssql->getRecord('student', array('id' => $idNumber));
	}

	// user/getEnrolledCourses
	public function userGetEnrolledCourses($userId, $catalog = null) {
		$enrolments = $this->userGetEnrolments2($userId);
		$enrol = array();

		foreach($enrolments as $enrolment) {
			$userEnrol = $this->enrolGetInfo($enrolment['enrolid']);

			$course = $this->courseGetInfo($userEnrol['courseid']);

            if (is_numeric($catalog) === true) {
                if ($course['catalog'] != $catalog) {
                    continue;
                }
            }

            $array = array('courseid' => $userEnrol['courseid'], 'roleid' => $userEnrol['roleid'], 'shortname' => $course['shortname']);

			if (empty($course['catalog']) == false) {
                $array['catalog'] = $course['catalog'];
                $array['semestr'] = $course['semestr'];
            }

            $enrol[] = $array;
		}

		return $enrol;
	}

	// user/getCurrentCourse
	public function userGetCurrentCourse($userId) {
		$base = $this->userGetBase($userId);
		$enrol = $this->userGetEnrolledCourses($userId, $base['catalog']);

		if (is_array($enrol) === false) {
			return $false;
		}

		return $enrol[0]['courseid'];
	}

	// user/courseSequence
	public function userCourseSequence($userId) {
		//$currentSemestr = $this->userGetCurrentCourse($userId);

		$base = $this->userGetBase($userId);
		
		$semestres = array();
		for ($i = 0; $i < $base['semestr_end']; $i++) {
			$semestres[] = $this->courseGetInfo($this->getCourseByIdnumber($base['catalog'].'-'.($i+1)));
		}

		return $semestres;
	}


	// course/next
	public function courseNext($courseId) {
		$course = $this->courseGetInfo($courseId);

		return $this->getCourseByIdnumber($course['catalog'].'-'.($course['semestr']+1));
	}



	// availability/encode
	public function availabilityEncode($availabilityConditions, $option = '&') {
		// availability - {"op":"&","c":[{"type":"profile","sf":"idnumber","op":"isequalto","v":"8051"}],"showc":[true]}

		$availability = (object)array();

		$availability->op = $option;

		$availability->c = array();
		$availability->showc = array();

		$i = 0;
		foreach ($availabilityConditions as $type => $id) {
			$availability->c[$i] = (object)array();

			$availability->c[$i]->type = $type;
			$availability->c[$i]->id = (int)$id;

			$availability->showc[$i] = true;
			$i++;
		}

		return addslashes(json_encode($availability));
	}

	// user/group/getByName
	public function groupGetByName($courseId, $groupName) {
		return $this->_mysqli->getRecordId('groups', array('courseid' => $courseId, 'name' => ['%'.$groupName.'%', 'LIKE']), 'id');
	}

    // user/group/getByName
    public function groupGetByIdnumber($courseId, $groupName) {
        return $this->_mysqli->getRecordId('groups', array('courseid' => $courseId, 'idnumber' => ['%'.$groupName.'%', 'LIKE']), 'id');
    }

	// user/group/create
	public function groupCreate($groupName, $courseId, $idnumber = NULL, $password = NULL, $description = '') {
		$groupId = $this->groupGetByName($courseId, $groupName);

		if ($groupId > 0) {
			return $groupId;
		}


		if (is_null($password) === true) {
			$password = mb_substr(hash('sha512', rand()), 0, 20);
		}

		return $this->_mysqli->insertRecord('groups', array('name' => $groupName, 'idnumber' => $idnumber, 'enrolmentkey' => $password,
			                                                'hidepicture' => 0, 'courseid' => $courseId, 'timecreated' => time(),
			                                                'timemodified' => time(), 'description' => $description, 'descriptionformat' => 1));
	}

	// user/group/update
	private function _groupUpdate($groupId) {
		return $this->_mysqli->updateRecord('groups', array('timemodified' => time()), array('id' => $groupId));
	}

	// user/group/assign
	public function groupAssign($userId, $groupId) {
		$inGroup = $this->_mysqli->getRecord('groups_members', array('groupid' => $groupId, 'userid' => $userId), 'timeadded');
		if (is_array($inGroup) === true) {
			echo "<b>Пользователь уже в группе</b><br>";
			return false;
		}

		$result = $this->_mysqli->insertRecord('groups_members', array('groupid' => $groupId, 'userid' => $userId, 'timeadded' => time(), 'component' => '', 'itemid' => 0));
		if ($result === false) {
			return false;
		}
		$this->_groupUpdate($groupId);
		return $this->_mysqli->insert_id;
	}

	// user/group/unassign
	public function groupUnassign($userId, $groupId) {
		$this->_groupUpdate($groupId);
		return $this->deleteRecord('groups_members', array('groupid' => $groupId, 'userid' => $userId));
	}

	// course/create
	public function createCourse($category, $startSemestr = 1) {
		$time = time();

		$courseDefaults = array('category' => $category['id'], 'visible' => 1, 'startdate' => strtotime('00:00:00'),
			                    'format' => 'topics', 'lang' => '','newsitems' => 0, 'showgrades' => 0,
			                    'showreports' => 0, 'legacyfiles' => 2, 'maxbytes' => 0, 'enablecompletion' => 0,
			                    'groupmode' => 0, 'groupmodeforce' => 0, 'defaultgroupingid' => 0,
			                    'timecreated' => $time, 'timemodified' => $time, 'sortorder' => 0,
			                    'summaryformat' => 1, 'visibleold' => 1);

		$enrolDefaults = array('enrolstartdate' => 0, 'enrolenddate' => 0, 'timemodified' => $time, 'timecreated' => $time,
		    	               'roleid' => 5, 'enrolperiod' => 0, 'expirynotify' => 0, 'notifyall' => 0, 'expirythreshold' => 86400);

		$blocks = array('search_forums','news_items','calendar_upcoming','recent_activity');
		$blockDefaults = array('showinsubcontexts' => 0, 'pagetypepattern' => 'course-view-*', 'defaultregion' => 'side-post', 'configdata' => '');

		$categoryContext = $this->getContext($category['id'], CONTEXT_COURSECAT);

		$categoryInfo = $this->_mysqli->getRecord('course_categories', array('id' => $category['id']));

		for ($semestr = $startSemestr; $semestr <= $category['maxsemestr']; $semestr++) {
			$course = array('fullname' => $semestr." семестр", 'shortname' => $semestr.'-'.$category['shortname'],
				            'idnumber' => $categoryInfo['idnumber']."-".$semestr, 'sortorder' => ($categoryInfo['sortorder'] + $semestr));

			$course['summary'] = "<p><span style=\"color: #000000;\" color=\"#000000\">
		                          <strong><em><span style=\"text-decoration: underline;\">".$category['type'].":</span></em></strong> \"".$categoryInfo['name']."\"<br />
		                          <strong><em><span style=\"text-decoration: underline;\">Базовое образование:</span></em></strong> ".$category['education']."<br />
		                          <strong><em><span style=\"text-decoration: underline;\">Семестр обучения:</span></em></strong> ".$semestr."<br /></span></p>";

		    $course += $courseDefaults;

		    $newCourseId = $this->_mysqli->insertRecord('course', $course);

		    // создаем контекст для курса
		    $newCourseContextId = $this->createContext($newCourseId, CONTEXT_COURSE, $categoryContext);

		    $courseFormatOptions = array(array('courseid' => $newCourseId,'format' => 'topics','sectionid' => 0,'name' => 'numsections', 'value' => 10),
		    	                         array('courseid' => $newCourseId,'format' => 'topics','sectionid' => 0,'name' => 'hiddensections', 'value' => 1),
		    	                         array('courseid' => $newCourseId,'format' => 'topics','sectionid' => 0,'name' => 'coursedisplay', 'value' => 0));

		    foreach($courseFormatOptions as $courseFormatOption) {
		    	$this->_mysqli->insertRecord('course_format_options', $courseFormatOption);
			}

		    foreach ($blocks as $num => $blockName) {
		    	$block = array('parentcontextid' => $newCourseContextId, 'blockname' => $blockName, 'defaultweight' => $num);
		    	$block += $blockDefaults;

		    	$newBlockId = $this->_mysqli->insertRecord('block_instances', $block);

		    	$this->createContext($newBlockId, CONTEXT_BLOCK, $newCourseContextId);
		    }

		    $this->_mysqli->insertRecord('course_sections', array('course' => $newCourseId, 'section' => 0, 'summary' => '', 'summaryformat' => 1, 'sequence' => ''));

		    for ($i = 1; $i <= 7; $i++) {
		    	$this->_mysqli->deleteRecord('role_names', array('contextid' => $newCourseContextId, 'roleid' => $i));
		    }

		    $enrol = array('enrol' => 'manual', 'status' => 0, 'courseid' => $newCourseId, 'sortorder' => 0);
		    $enrol += $enrolDefaults;

		    $this->_mysqli->insertRecord('enrol', $enrol);

		    $enrol = array('enrol' => 'self', 'status' => 1, 'courseid' => $newCourseId, 'sortorder' => 1,
		    	           'customint1' => 0, 'customint2' => 0, 'customint3' => 0, 'customint4' => 1, 'customint5' => 0, 'customint6' => 1);
		    $enrol += $enrolDefaults;

			$this->_mysqli->insertRecord('enrol', $enrol);

		}

		if ($this->_mysqli->updateRecord('course_categories', array('coursecount' => $category['maxsemestr']), array('id' => $category['id'])) === true) {
			echo "Курсы для категории ".$categoryInfo['name']." созданы!";
		}

		return true;
	}


	// course/cacheUpdate
	public function courseCacheUpdate($courseId) {
		// increment_revision_number lib/datalib.php
		$time = time();

		return $this->_mysqli->_query("UPDATE edu_course
                   SET cacherev = (CASE
                       WHEN cacherev IS NULL THEN ".$time."
                       WHEN cacherev < ".$time." THEN ".$time."
                       WHEN cacherev > ".$time." + 3600 THEN ".$time."
                       ELSE cacherev + 1 END) WHERE id = '".$courseId."';");
	}


	// course/getInfo
	public function courseGetInfo($courseId) {
		$courseResult = $this->_mysqli->getRecord('course', array('id' => $courseId), array('id', 'category', 'shortname', 'idnumber'));

		if (in_array($courseId, $this->managementSOCourse) === true) {
			$courseResult['semestr'] = array_search($courseId, $this->managementSOCourse);
		}

		if (in_array($courseId, $this->managementSPOCourse) === true) {
			$courseResult['semestr'] = array_search($courseId, $this->managementSPOCourse);
		}

        $courseShortName = explode("-", $courseResult['idnumber']);

        if (sizeof($courseShortName) >= 2) {
        	$courseResult['semestr'] = $courseShortName[1];
        	$courseResult['catalog'] = $courseShortName[0];
        }

        return $courseResult;
	}

	// course/usercount
	public function courseUserCount($courseId, $roleId = 5) {
		$courseContext = $this->getContext($courseId, CONTEXT_COURSE);

		$count = $this->_mysqli->getRecord('role_assignments', array('roleid' => $roleId, 'contextid' => $courseContext));
		//SELECT count(userid) FROM edu_role_assignments WHERE roleid=5 AND contextid=(SELECT id FROM edu_context WHERE contextlevel=50 AND instanceid=286)
		return sizeof($count);
	}

	// количество секций в курсе
	public function getCourseSectionsCount($courseId) {
		return $this->_mysqli->getRecordId('course_format_options', array('courseid' => $courseId, 'name' => 'numsections'), 'value');
	}

	// обновить количество секций в курсе
	// course/updateSectionsCount
	public function updateCourseSectionsCount($courseId, $numsections) {
		return $this->_mysqli->updateRecord('course_format_options', array('value' => $numsections), array('courseid' => $courseId, 'name' => 'numsections'));
	}


	public function findSectionByName($courseId, $name) {
		return $this->_mysqli->getRecordId('course_sections', array('name' => ["%".$name."%", 'LIKE'], 'course' => $courseId), 'id');
	}

	// course/section/show
	public function sectionShow($sectionId) {
		return $this->_mysqli->updateRecord('course_sections', array('visible' => 1), array('id' => $sectionId));
	}

	// создать секцию
	// course/section/create()
	public function createCourseSection($courseId, $name = '', $summary = '', $existing = 1) {
		if ($existing === 1) {
			$sectionId = $this->findSectionByName($courseId, $name);

			if ($sectionId !== false) {
				$this->sectionShow($sectionId);
				return $sectionId;
			}
		}

		$sectionQuery = $this->_mysqli->getRecordId('course_sections', array('course' => $courseId, 'sequence' => '', 'name' => '', 'section' => [0,'>']));
		//_query("select section from edu_course_sections where course=".$courseId." and sequence='' and name is null and section > 0 order by section limit 1");

		if ($sectionQuery != false) {
			$section = $sectionQuery;
		} else {
			$section = $this->getCourseSectionsCount($courseId) + 1;
			$this->updateCourseSectionsCount($courseId, $section);
		}

		return $this->_mysqli->insertRecord('course_sections', 
			                   array('course' => $courseId, 'section' => $section, 'name' => $name, 'summary' => $summary, 'summaryformat' => 1, 'sequence' => '', 'visible' => 1));
	}

	// course/section/getCourse()
	public function sectionGetCourse($sectionId) {
		return $this->_mysqli->getRecordId('course_sections', array('id' => $sectionId), 'course');
	}

	// course/section/sequenceGet()
	public function sectionGetSequence($sectionId) {
		return $this->_mysqli->getRecordId('course_sections', array('id' => $sectionId), 'sequence');
	}

	// course/section/sequenceUpdate()
	public function sectionUpdateSequence($sectionId, $newSequence, $order = 'post') {
		$sequence = $this->sectionGetSequence($sectionId);

		if (empty($sequence) === true) {
			$sequence = $newSequence;
		} else {
			if ($order === 'pre') {
				$sequence = $newSequence.",".$sequence;
			} else {
				$sequence .= ",".$newSequence;
			}
		}

		return $this->_mysqli->updateRecord('course_sections', array('sequence' => $sequence), array('id' => $sectionId));
	}

	// course/section/addrestriction
	public function sectionAddRestriction($sectionId, $availabilityConditions) {
		return $this->_mysqli->updateRecord('course_sections', array('availability' => $this->availabilityEncode($availabilityConditions)), array('id' => $sectionId));
	}

	// course/section/getLabel
	public function sectionGetLabel($sectionId) {
		$sequence = $this->sectionGetSequence($sectionId);

		if (empty($sequence) === true) {
			return false;
		}

		$firstModule = explode(',', $sequence)[0];

        $preModuleQuery = $this->_mysqli->query("SELECT b.id, b.intro FROM edu_course_modules a 
                                   LEFT JOIN edu_label b ON a.instance=b.id 
                                   WHERE a.module=10 AND a.id='".$firstModule."'")->fetch_assoc();

        if ($preModuleQuery === false) {
        	return false;
        }
        return array('id' => $preModuleQuery['id'], 'text' => $preModuleQuery['intro']);
	}

	// course/section/updateLabel
	public function sectionUpdateLabel($sectionId, $text, $append=true) {
		$label = $this->sectionGetLabel($sectionId);

		if ($append === true) {
			$text = $label['text']."\n".$text;
		}

		return $this->_mysqli->updateRecord('label', array('intro' => $text), array('id' => $label['id']));
	}

	// course/section/getPrefix
	public function sectionGetPrefix($sectionId, $delimiter = ') ') {
		$label = $this->sectionGetLabel($sectionId);

    	if (preg_match_all("|<p>(\d+)\).+</p>|iU", $label['text'], $result) > 0) {
    		return (intval(end($result[1])) + 1).$delimiter;
    	}
    	return "1".$delimiter;
	}

	// course/fillSections
	public function coursefillSections($courseId)
    {
        $course = $this->courseGetInfo($courseId);

        $result = $this->_mssql->_query("SELECT b.name,a.control_type FROM dbo.journal a 
        	                             LEFT JOIN dbo.disciplines b ON a.discipline=b.id
                               			 WHERE a.id=".$this->categoriesArray[$course['category']]." AND a.semestr=".$course['semestr']." 
                               			 ORDER BY b.name");

        $i = 1;
        $courseWorks = array();

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            if (strcmp($row['control_type'], "курсовая работа") === 0) {
                $courseWorks[] = $row['name'];
                continue;
            }

            if (strcmp($row['name'], "Иностранный язык (английский)") === 0 ||
                strcmp($row['name'], "Иностранный язык (немецкий)") === 0 ||
                strcmp($row['name'], "Начертательная геометрия и инженерная графика") === 0 ||
                strcmp($row['name'], "Инженерная графика") === 0) {
                $courseWorks[] = $row['name'];
            }

            $this->_mysqli->updateRecord('course_sections', array('name' => $row['name']), array('course' => $courseId, 'section' => $i));
            $i++;
        }

        $text = '';
        if (sizeof($courseWorks) > 0) {
            $text = "<li>До начала сессии (до ".$this->sessionStartDate.") необходимо выполнить работы и прислать нам по следующим дисциплинам:</li>
                     <ul style=\"color: #0000cc;\">";

            for ($i = 0; $i < sizeof($courseWorks); $i++) {
                $text .= "<li>".$courseWorks[$i];
                if ($i === sizeof($courseWorks) - 1) {
                    $text .= ".";
                } else {
                    $text .= ";";
                }
                $text .="</li>";
            }

            $text .= "</ul>";
        }

        $this->_mysqli->updateRecord('course_sections', array('summary' => "<h3 style=\"font-weight: bold\"><font size=\"3\">Уважаемые студенты!</font></h3>
                        <ol style=\"font-weight: bold;\">".$text."
                        <li>Отвечая на экзамен или зачет где необходимо прикрепить файл, Вы можете прикрепить в любом формате, какой для Вас удобнее.</li>
                        <li>Если в сводной ведомости стоит перезачет дисциплины, то её сдавать не надо.</li>
                        <li style=\"text-decoration: underline; color: #ff0000;\">Экзамены/зачеты длятся разное время. Рассчитывайте, пожалуйста, 
                        время начала тестирования, чтобы успеть закончить до 23:59.</li>
                        <li style=\"color: #ff0000;\">Оценки за экзамены и зачеты Вы увидите только после сессии в сводной ведомости, которая находится в личном кабинете.</li></ol>"),
        				array('course' => $courseId, 'section' => 0));

        $this->updateCourseSectionsCount($courseId, $i-1);
        $this->courseCacheUpdate($courseId);
        return true;
    }

    // course/section/copy
    function courseSectionCopy($from, $to, $enumerate = true) {
    	$fromName = $this->_mysqli->query("SELECT name FROM edu_course_sections WHERE id='".$from."'")->fetch_array(MYSQLI_ASSOC);
    	$toResult = $this->_mysqli->query("SELECT name, course, summary, section, sequence FROM edu_course_sections WHERE id='".$to."'")->fetch_array(MYSQLI_ASSOC);

    	$toCourse = $toResult['course'];

    	$contextResult = $this->getContext($toCourse, 50);

    	$modulesToCopyQuery = $this->_mysqli->query("SELECT a.id, a.module,b.name,a.instance FROM edu_course_modules a 
                               LEFT JOIN edu_modules b ON a.module=b.id 
                               WHERE section='".$from."'");
    	$modules = array();

    	// нумерация элементов курса и добавление текста в описание раздела
    	if ($enumerate === true) {
        	$prefix = $this->sectionGetPrefix($to);
    	} else {
        	$prefix = '';
    	}

    	// копируем все модули
    	while ($fromResult = $modulesToCopyQuery->fetch_array(MYSQLI_ASSOC)) {
        	try {
            	// start try
            	$this->_mysqli->autocommit(FALSE); // start transaction

            	$copyQuery = "DROP TABLE IF EXISTS foo1;
                              CREATE TEMPORARY TABLE `foo1` AS SELECT * FROM `edu_".$fromResult['name']."` 
                                                           WHERE id =".$fromResult['instance'].";
                          ALTER TABLE foo1 MODIFY `id` BIGINT(10) NULL; 
                          UPDATE foo1 SET `id`=NULL, `course`=".$toCourse.", `name`=CONCAT('".$prefix."', `name`);
                          INSERT INTO edu_".$fromResult['name']." SELECT * FROM foo1; 
                          DROP TABLE foo1; 
                          SELECT LAST_INSERT_ID() as id";

            	$res = $this->_mysqli->multiQueryGetResult($copyQuery);

            	if ($res === false) {
                	throw new Exception($this->_mysqli->error);
            	}

            	// переносим события в календаре для модулей типа quiz и assign
            	if (strcmp($fromResult['name'], "quiz") === 0 || 
                	strcmp($fromResult['name'], "assign") === 0) {
                	$eventQuery = "DROP TABLE IF EXISTS fooevent;
                               CREATE TEMPORARY TABLE `fooevent` AS SELECT * FROM `edu_event` WHERE instance = ".$fromResult['instance']." AND modulename = '".$fromResult['name']."'; 
                               UPDATE `fooevent` SET id=0, courseid=".$toCourse.", instance=".$res.";
                               INSERT INTO edu_event SELECT * FROM fooevent; 
                               DROP TABLE IF EXISTS fooevent;";
                
                	if ($this->_mysqli->multi_query($eventQuery) === false) {
                    	throw new Exception("Ошибка при переносе в календаре модуля ".$fromResult['name']." (".$fromResult['instance'].")");
                	} else {
                    	if ($this->_mysqli->more_results() === true) {
                        	while ($this->_mysqli->more_results()) {$this->_mysqli->next_result();}
                    	}
                	}

                	while($this->_mysqli->next_result()) $this->_mysqli->store_result();
            	}

            	$newModuleId = $this->createModule($to, $fromResult['module'], $res);
            	// id context копируемого модуля
            	$oldContextId = $this->getContext($fromResult['id'], 70);
            	// создание нового context для созданного модуля
            	$newContextId = $this->createContext($newModuleId, 70, $contextResult);

            	echo "Модуль ".$res." успешно скопирован (новый идентификатор ".$newModuleId.")!<br>";
            	$modules[] = $newModuleId;

            	// label (10), quiz (13), resource (14), page, url, assign (25)
            	switch ($fromResult['module']) {
            	case '13':
                // quiz
                	$oldQuizSlots = $this->_mysqli->query("SELECT a.id, a.questionid, b.category, b.qtype FROM edu_quiz_slots a 
                                              LEFT JOIN edu_question b ON a.questionid=b.id 
                                              WHERE quizid='".$fromResult['instance']."';");

                	while ($qsr = $oldQuizSlots->fetch_array(MYSQLI_ASSOC)) {
                    	if (strcmp($qsr['qtype'], 'random') === 0) {
                        	$questionCategory = $this->_mysqli->query("SELECT name FROM edu_question_categories WHERE id='".$qsr['category']."' LIMIT 1;")->fetch_array(MYSQLI_ASSOC);

                        	if ($this->_mysqli->query("INSERT INTO edu_question SET category='".$qsr['category']."',
                                                                       name='Случайный (".$questionCategory['name'].")', 
                                                                       qtype='random', 
                                                                       parent=0, 
                                                                       questiontext=0, 
                                                                       generalfeedback='', 
                                                                       penalty=0") === false) {
                            	throw new Exception("Ошибка при создании случайного вопроса", 13);
                        	} else {
                            	$qsr['questionid'] = $this->_mysqli->insert_id;
                            	$this->_mysqli->query("UPDATE edu_question SET parent='".$qsr['questionid']."' WHERE id='".$qsr['questionid']."';");

                            	echo "Создан случайный вопрос.<br>";
                        	}
                    	} 
/*
INSERT INTO edu_quiz_sections SET quizid='', firstslot='1';

INSERT INTO edu_question (qtype,category,name,parent,length,penalty,questiontext,questiontextformat,generalfeedback,generalfeedbackformat,defaultmark,stamp,createdby,timecreated) VALUES('random','107','-','0','1','0','0','0','','0','1','moodle.ins-iit.ru+150526105554+74laja','2','1432637754')

UPDATE edu_question SET qtype = 'random',category = '107',name = 'Случайный (Химия (1 из 1) (2 из 2))',parent = '19903',length = '1',penalty = '0',questiontext = '0',questiontextformat = '0',generalfeedback = '',generalfeedbackformat = '0',defaultmark = '1',stamp = 'moodle.ins-iit.ru+150526105554+74laja',version = 'moodle.ins-iit.ru+150526105555+odG7ls',createdby = '2',timecreated = '1432637754',modifiedby = '2',timemodified = '1432637754' WHERE id='19903'
INSERT INTO edu_quiz_slots (quizid,questionid,maxmark,slot,page) VALUES('2981','19903','1.0000000','1','1')
INSERT INTO edu_quiz_slots (quizid,questionid,maxmark,slot,page) VALUES('2981','203','5.0000000','2','2')
*/
                    	if ($this->_mysqli->query("INSERT INTO edu_quiz_slots (slot, quizid, page, questionid, maxmark) 
                                                  SELECT slot, '".$res."', page, '".$qsr['questionid']."', maxmark FROM edu_quiz_slots WHERE id='".$qsr['id']."'") === false) {
                        	throw new Exception("Ошибка при создании слота вопросов", 13);
                    	}
                	}

                	if ($this->_mysqli->query("INSERT INTO edu_quiz_sections (quizid, firstslot, heading, shufflequestions) 
                                  SELECT '".$res."', firstslot, heading, shufflequestions FROM edu_quiz_sections WHERE quizid='".$fromResult['instance']."'") === false) {
                    	throw new Exception("Ошибка при создании секций теста", 15);
                	}

                	if ($this->_mysqli->query("UPDATE edu_quiz SET sumgrades = COALESCE((
                                                            SELECT SUM(maxmark) FROM edu_quiz_slots
                                                            WHERE quizid = edu_quiz.id
                                                          ), 0) WHERE id = '".$res."';") === false) {
                    	throw new Exception("Ошибка при пересчете максимальной оценки", 16);
                	}
                	
                	echo "Тест успешно скопирован.<br>";
                	break;

            	case '14':
                	// resource
                	$resourceQuery = "DROP TABLE IF EXISTS `resourcecopy`; 
                                  CREATE TEMPORARY TABLE `resourcecopy` AS SELECT * FROM edu_files WHERE contextid='".$oldContextId."';
                                  UPDATE `resourcecopy` SET id=0, 
                                                            contextid='".$newContextId."', 
                                                            pathnamehash=SHA1(CONCAT('/', '".$newContextId."', '/', `component`, '/', `filearea`,'/',`itemid`,`filepath`,`filename`)); 
                                  INSERT INTO `edu_files` SELECT * FROM `resourcecopy`; 
                                  DROP TABLE `resourcecopy`; 
                                  SELECT LAST_INSERT_ID() as id;";

                	$resourceId = $this->_mysqli->multiQueryGetResult($resourceQuery);

                	if ($resourceId > 0) {
                    	echo "Файл успешно скопирован.<br>";
                	} else {
                    	throw new Exception("Ошибка при копировании ресурса", 14);
                	}

                	break;

            	case '23':
                	// page
                	echo "Страница успешно скопирована.<br>";
                	break;

            	case '25':
                	// assign
                	echo "Задание успешно скопировано.<br>";
                	break;
            	}
            	
            	$this->_mysqli->autocommit(TRUE);
        	}
        	catch ( Exception $e ) {
            	echo "Ошибка: " . $e->getMessage();
            	echo $this->_mysqli->error;
            	$this->_mysqli->rollback();
            	$this->_mysqli->autocommit(TRUE);
        	}
        	// end try
    	}

    	$this->sectionUpdateSequence($to, implode(',', $modules));

    	if (empty($prefix) === false) {
        	$this->sectionUpdateLabel($to, "<p>".$prefix.$fromName['name']."</p>");
    	}

    	$this->courseCacheUpdate($toCourse);

    	echo "<br>Раздел <a href=\"http://moodle.ins-iit.ru/course/view.php?id=".$toCourse."#section-".$toResult['section']."\">\"".
          	 strip_tags($toResult['name'])."\"</a> успешно скопирован из раздела \"".strip_tags($fromName['name'])."\".";
	}

	// course/module/create()
	public function createModule($sectionId, $module, $moduleId) {
		$courseId = $this->sectionGetCourse($sectionId);

		$module = array('course' => $courseId, 'module' => $module, 'section' => $sectionId, 'instance' => $moduleId,
			            'visible' => 1, 'visibleold' => 1, 'groupmode' => 0, 'groupingid' => 0, 'availability' => NULL, 'showdescription' => '0', 'added' => time());

		$newModuleId = $this->_mysqli->insertRecord('course_modules', $module);

		$this->createContext($newModuleId, CONTEXT_MODULE, $courseId);
		return $newModuleId;
	}

	// создать предварительное вступление
	// course/module/label/create
	public function labelCreate($sectionId, $text = '', $order = 'post') {
		try {
			$this->_mysqli->autocommit(false);

			$courseId = $this->sectionGetCourse($sectionId);

			$courseContext = $this->getContext($courseId, 50);

			$id = $this->_mysqli->insertRecord('label', array('course' => $courseId, 'name' => '', 'intro' => $text, 'introformat' => '1', 'timemodified' => time()));

			$moduleId = $this->createModule($sectionId, 10, $id);
			if ($moduleId === false) {
				throw new Exception("Ошибка при создании модуля \"Пояснение\" в разделе ".$sectionId.".");
			}

			$newContextId = $this->createContext($moduleId, 70, $courseContext);

			$this->sectionUpdateSequence($sectionId, $moduleId, $order);

			$this->courseCacheUpdate($courseId);

			$this->_mysqli->autocommit(true);
		} catch (Exception $e) {
			echo "Ошибка: " . $e->getMessage();
            echo $this->_mysqli->error;
            $this->_mysqli->rollback();
            $this->_mysqli->autocommit(true);
		}

		return $id;
	}
}


/*
$category = array('id' => 78, 'type' => "Направление подготовки", 'education' => "среднее (полное) общее образование", 'shortname' => 'ЭЭ-Б-СО', 'maxsemestr' => 10);

$mdl->createCourse($category, 2);
*/
//$mdl->syncUsers();
