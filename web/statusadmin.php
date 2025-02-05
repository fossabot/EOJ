<?php
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
////////////////////////////Common head
$cache_time = 2;
$OJ_CACHE_SHARE = false;
require_once('./include/cache_start.php');
require_once('./include/db_info.inc.php');
require_once('./include/memcache.php');
require_once('./include/setlang.php');
$view_title = "$MSG_STATUS";

function formatTimeLength($length)
{
  $hour = 0;
  $minute = 0;
  $second = 0;
  $result = '';

  global $MSG_SECONDS, $MSG_MINUTES, $MSG_HOURS, $MSG_DAYS;

  if ($length >= 60) {
    $second = $length % 60;

    if ($second > 0 && $second < 10) {
      $result = '0' . $second . ' ' . $MSG_SECONDS;
    } else if ($second > 0) {
      $result = $second . ' ' . $MSG_SECONDS;
    }

    $length = floor($length / 60);
    if ($length >= 60) {
      $minute = $length % 60;

      if ($minute == 0) {
        if ($result != '') {
          $result = '00' . ' ' . $MSG_MINUTES . ' ' . $result;
        }
      } else if ($minute > 0 && $minute < 10) {
        if ($result != '') {
          $result = '0' . $minute . ' ' . $MSG_MINUTES . ' ' . $result;
        }
      } else {
        $result = $minute . ' ' . $MSG_MINUTES . ' ' . $result;
      }

      $length = floor($length / 60);

      if ($length >= 24) {
        $hour = $length % 24;

        if ($hour == 0) {
          if ($result != '') {
            $result = '00' . ' ' . $MSG_HOURS . ' ' . $result;
          }
        } else if ($hour > 0 && $hour < 10) {
          if ($result != '') {
            $result = '0' . $hour . ' ' . $MSG_HOURS . ' ' . $result;
          }
        } else {
          $result = $hour . ' ' . $MSG_HOURS . ' ' . $result;
        }

        $length = floor($length / 24);
        $result = $length . ' ' . $MSG_DAYS . ' ' . $result;
      } else {
        $result = $length . ' ' . $MSG_HOURS . ' ' . $result;
      }
    } else {
      $result = $length . ' ' . $MSG_MINUTES . ' ' . $result;
    }
  } else {
    $result = $length . ' ' . $MSG_SECONDS;
  }
  return $result;
}

require_once("./include/my_func.inc.php");

if (isset($OJ_LANG)) {
  require_once("./lang/$OJ_LANG.php");
}

require_once("./include/const.inc.php");

if (!(isset($_SESSION[$OJ_NAME . '_' . 'administrator']) || isset($_SESSION[$OJ_NAME . '_' . 'source_browser']))) {
  $view_swal = $MSG_WARNING_ACCESS_DENIED;
  require("template/error.php");
  exit(0);
}
$judge_color = array("label label-info", "label label-info", "label label-warning", "label label-warning", "label label-success", "label label-danger", "label label-danger", "label label-warning", "label label-warning", "label label-warning", "label label-warning", "label label-warning", "label label-warning", "label label-info");

$str2 = "";
$lock = false;
$lock_time = date("Y-m-d H:i:s", time());

$sql = "WHERE problem_id>0 ";

if (isset($_GET['cid']) and $_GET['cid'] != "" and $_GET['cid'] != "0") {
  $cid = intval($_GET['cid']);
  $sql = $sql . " AND `contest_id`='$cid' and num>=0 ";
  $str2 = $str2 . "&cid=$cid";
  $sql_lock = "SELECT `start_time`,`title`,`end_time` FROM `contest` WHERE `contest_id`=?";
  $result = pdo_query($sql_lock, $cid);
  $rows_cnt = count($result);
  $start_time = 0;
  $end_time = 0;

  if ($rows_cnt > 0) {
    $row = $result[0];
    $start_time = strtotime($row[0]);
    $title = $row[1];
    $end_time = strtotime($row[2]);

    $noip = (time() < $end_time) && (stripos($title, $OJ_NOIP_KEYWORD) !== false);
    if (
      isset($_SESSION[$OJ_NAME . '_' . "administrator"]) ||
      isset($_SESSION[$OJ_NAME . '_' . "m$cid"]) ||
      isset($_SESSION[$OJ_NAME . '_' . "source_browser"]) ||
      isset($_SESSION[$OJ_NAME . '_' . "contest_creator"])
    ) $noip = false;
    if ($noip) {
      $view_errors =  "<h2> $MSG_NOIP_WARNING <a href=\"contest.php?cid=$cid\">返回比赛</a></h2>";
      $refererUrl = parse_url($_SERVER['HTTP_REFERER']);
      if ($refererUrl['path'] == "/submitpage.php")
        $view_errors = "<h2>提交成功!</h2><a href=\"contest.php?cid=$cid\">返回比赛</a></h2>";
      require("template/error.php");
      exit(0);
    }
  }

  $lock_time = $end_time - ($end_time - $start_time) * $OJ_RANK_LOCK_PERCENT;
  //$lock_time=date("Y-m-d H:i:s",$lock_time);
  $time_sql = "";
  //echo $lock.'-'.date("Y-m-d H:i:s",$lock);
  if (time() > $lock_time && time() < $end_time) {
    //$lock_time=date("Y-m-d H:i:s",$lock_time);
    //echo $time_sql;
    $lock = true;
  } else {
    $lock = false;
  }

  //require_once("contest-header.php");
} else {
  //require_once("oj-header.php");
  if (
    isset($_SESSION[$OJ_NAME . '_' . 'administrator'])
    || isset($_SESSION[$OJ_NAME . '_' . 'source_browser'])
    || (isset($_SESSION[$OJ_NAME . '_' . 'user_id'])
      && (isset($_GET['user_id']) && $_GET['user_id'] == $_SESSION[$OJ_NAME . '_' . 'user_id']))
  ) {
    if (isset($_SESSION[$OJ_NAME . '_' . 'source_browser'])) {
      $sql = "WHERE problem_id>0  ";
    } else if ($_SESSION[$OJ_NAME . '_' . 'user_id'] != "guest")
      $sql = "WHERE (contest_id=0 or contest_id is null)  ";
  } else {
    $sql = "WHERE  (contest_id=0 or contest_id is null)  and problem_id>0   ";
  }
}

$start_first = true;
$order_str = " ORDER BY `solution_id` DESC ";

// check the top arg
if (isset($_GET['top'])) {
  $top = strval(intval($_GET['top']));
  if ($top != -1)
    $sql = $sql . "AND `solution_id`<='" . $top . "' ";
}

// check the problem arg
$problem_id = "";
if (isset($_GET['problem_id']) && $_GET['problem_id'] != "") {
  if (isset($cid)) {
    $problem_id = htmlentities($_GET['problem_id'], ENT_QUOTES, 'UTF-8');
    $num = array_search($problem_id, $PID);
    $problem_id = $PID[$num];
    $sql = $sql . "AND `num`='" . $num . "' ";
    $str2 = $str2 . "&problem_id=" . trim($problem_id);
  } else {
    $problem_id = strval(intval($_GET['problem_id']));
    if ($problem_id != '0') {
      $sql = $sql . "AND `problem_id`='" . $problem_id . "' ";
      $str2 = $str2 . "&problem_id=" . trim($problem_id);
    } else
      $problem_id = "";
  }
}

// check group arg
if (isset($_GET['gid']) && $_GET['gid'] != "" && $_GET['gid'] != "-1") {
  $gid = trim($_GET['gid']);
  $sql_gname = "SELECT `name` FROM `group` WHERE `gid`= ? ;";
  $result = pdo_query($sql_gname, $gid);
  $group_name = $result[0]["name"];
  $sql_g2u = "SELECT `user_id` FROM `users` WHERE `gid` = ?;";
  $result = pdo_query($sql_g2u, $gid);
  $uid = array();
  foreach ($result as $i) {
    array_push($uid, $i["user_id"]);
  }
}

$sql_all = "SELECT * FROM `group`;";
$result = pdo_query($sql_all);
$all_group = $result;

// check the user_id arg
$user_id = "";
if (isset($OJ_ON_SITE_CONTEST_ID) && $OJ_ON_SITE_CONTEST_ID > 0 && !isset($_SESSION[$OJ_NAME . '_' . 'administrator'])) {
  $_GET['user_id'] = $_SESSION[$OJ_NAME . '_' . 'user_id'];
}

if (isset($_GET['user_id']) && $_GET['user_id'] != "") {
  $user_id = trim($_GET['user_id']);
  if (is_valid_user_name($user_id) && $user_id != "") {
    $sql = $sql . "AND `user_id` LIKE '%$user_id%' ";
    if ($str2 != "")
      $str2 = $str2 . "&";

    $str2 = $str2 . "user_id=" . urlencode($user_id);
  } else
    $user_id = "";
} else if (isset($uid)) {
  $user_id = join(",", $uid);
  $sql = $sql . "AND `user_id` IN ($user_id) ";
}

if (isset($_GET["solution_id"]) && $_GET["solution_id"]) {
  $solution_id = intval($_GET["solution_id"]);
}

if (!$user_id and !$problem_id and (!isset($cid) or !$cid) && !$solution_id) {
  $view_errors = "<div class='alert alert-danger' role='alert'>$MSG_PARAMS_TOO_FEW</div>";
  $view_status = array();
  require("template/statusadmin.php");
  exit(0);
}

if (isset($_GET['language']))
  $language = intval($_GET['language']);
else
  $language = -1;

if ($language > count($language_ext) || $language < 0)
  $language = -1;

if ($language != -1) {
  $sql = $sql . "AND `language`='" . ($language) . "' ";
  $str2 = $str2 . "&language=" . $language;
}

if (isset($_GET['jresult']))
  $result = intval($_GET['jresult']);
else
  $result = -1;

if ($result > 12 || $result < 0)
  $result = -1;

if ($result != -1 && !$lock) {
  $sql = $sql . "AND `result`='" . ($result) . "' ";
  $str2 = $str2 . "&jresult=" . $result;
}

if ($OJ_SIM) {
  //$old=$sql;
  $sql = "select * from solution solution left join `sim` sim on solution.solution_id=sim.s_id " . $sql;
  if (isset($_GET['showsim']) && intval($_GET['showsim']) > 0) {
    $showsim = intval($_GET['showsim']);
    $sql .= " and sim.sim>=$showsim";
    $str2 .= "&showsim=$showsim";
  }

  //$sql=$sql.$order_str." LIMIT 20";
} else {
  $sql = "select * from `solution` " . $sql;
}

//echo $sql;

$sql = $sql . $order_str;
//echo $sql;

if (isset($solution_id)) {
  $sql = "SELECT * FROM `solution` WHERE solution_id = ?";
  $result = mysql_query_cache($sql, $solution_id);
} elseif (isset($_GET['user_id'])) {
  $result = pdo_query($sql, $user_id);
} else {
  $result = mysql_query_cache($sql);
}

if ($result)
  $rows_cnt = count($result);
else
  $rows_cnt = 0;

$top = $bottom = -1;
$cnt = 0;
if ($start_first) {
  $row_start = 0;
  $row_add = 1;
} else {
  $row_start = $rows_cnt - 1;
  $row_add = -1;
}

$view_status = array();

$last = 0;
for ($i = 0; $i < $rows_cnt; $i++) {
  $row = $result[$i];
  //$view_status[$i]=$row;
  if ($i == 0 && $row['result'] < 4)
    $last = $row['solution_id'];

  if ($top == -1)
    $top = $row['solution_id'];

  $bottom = $row['solution_id'];
  $flag = (!is_running(intval($row['contest_id'])))
    || isset($_SESSION[$OJ_NAME . '_' . 'source_browser'])
    || isset($_SESSION[$OJ_NAME . '_' . 'administrator'])
    || (isset($_SESSION[$OJ_NAME . '_' . 'user_id']) && !strcmp($row['user_id'], $_SESSION[$OJ_NAME . '_' . 'user_id']));

  $cnt = 1 - $cnt;

  $sid = $row['solution_id'];
  if (isset($_SESSION[$OJ_NAME . '_' . 'administrator'])) {
    $view_status[$i][0] = "<a href='admin/rejudge.php?sid=$sid'>$sid</a>";
  } else {
    $view_status[$i][0] = $sid;
  }

  if ($row['contest_id'] > 0) {
    if (isset($_SESSION[$OJ_NAME . '_' . 'administrator']))
      $view_status[$i][1] = "<a href='contestrank.php?cid=" . $row['contest_id'] . "&user_id=" . $row['user_id'] . "#" . $row['user_id'] . "' title='" . $row['ip'] . "'>" . $row['user_id'] . " " . $row['nick'] . "</a>";
    else
      $view_status[$i][1] = "<a href='contestrank.php?cid=" . $row['contest_id'] . "&user_id=" . $row['user_id'] . "#" . $row['user_id'] . "'>" . $row['user_id'] . "</a>";
  } else {
    if (isset($_SESSION[$OJ_NAME . '_' . 'administrator']))
      $view_status[$i][1] = "<a href='userinfo.php?user=" . $row['user_id'] . "' title='" . $row['nick'] . "[" . $row['ip'] . "]'>" . $row['user_id'] . " " . $row['nick'] . "</a>";
    else
      $view_status[$i][1] = "<a href='userinfo.php?user=" . $row['user_id'] . "' title='" . $row['nick'] . "'>" . $row['user_id'] . "</a>";
  }

  if ($row['contest_id'] > 0) {
    if (isset($end_time) && time() < $end_time) {
      $view_status[$i][2] = "<div class=center><a href='problem.php?cid=" . $row['contest_id'] . "&pid=" . $row['num'] . "'>";
      if (isset($cid)) {
        $view_status[$i][2] .= $PID[$row['num']];
      } else {
        $view_status[$i][2] .= $row['problem_id'];
      }
      $view_status[$i][2] .= "</div></a>";
    } else {
      $view_status[$i][2] = "<div class=center>";
      if (isset($cid)) {

        //check the problem will be use remained contest/exam
        $tpid = intval($row['problem_id']);
        $sql = "SELECT `problem_id` FROM `problem` WHERE `problem_id`=? AND `problem_id` IN (
          SELECT `problem_id` FROM `contest_problem` WHERE `contest_id` IN (
            SELECT `contest_id` FROM `contest` WHERE (`defunct`='N' AND now()<`end_time`)
          )
        )";

        $tresult = pdo_query($sql, $tpid);

        if (intval($tresult) != 0)   //if the problem will be use remaind contes/exam
          $view_status[$i][2] .= $PID[$row['num']]; //hide link
        else
          $view_status[$i][2] .= "<a href='problem.php?id=" . $row['problem_id'] . "'>" . $PID[$row['num']] . "</a>";
      } else {
        $view_status[$i][2] .= "<a href='problem.php?id=" . $row['problem_id'] . "'>" . $row['problem_id'] . "</a>";
      }
      $view_status[$i][2] .= "</div>";
    }
  } else {
    $view_status[$i][2] = "<div class=center><a href='problem.php?id=" . $row['problem_id'] . "'>" . $row['problem_id'] . "</a></div>";
  }

  switch ($row['result']) {
    case 4:
      $MSG_Tips = $MSG_HELP_AC;
      break;
    case 5:
      $MSG_Tips = $MSG_HELP_PE;
      break;
    case 6:
      $MSG_Tips = $MSG_HELP_WA;
      break;
    case 7:
      $MSG_Tips = $MSG_HELP_TLE;
      break;
    case 8:
      $MSG_Tips = $MSG_HELP_MLE;
      break;
    case 9:
      $MSG_Tips = $MSG_HELP_OLE;
      break;
    case 10:
      $MSG_Tips = $MSG_HELP_RE;
      break;
    case 11:
      $MSG_Tips = $MSG_HELP_CE;
      break;
    default:
      $MSG_Tips = "";
  }

  $AC_RATE = intval($row['pass_rate'] * 100);
  if (isset($OJ_MARK) && $OJ_MARK != "mark") {
    $mark = "";
  } else {
    if ($AC_RATE > 99)
      $mark = "";
    else
      $mark = " " . "AC:" . $AC_RATE . "%";
  }

  if ((!isset($_SESSION[$OJ_NAME . '_' . 'user_id']) || $row['user_id'] != $_SESSION[$OJ_NAME . '_' . 'user_id']) && !isset($_SESSION[$OJ_NAME . '_' . 'source_browser']))
    $mark = "";

  $view_status[$i][3] = "<span class='hidden' style='display:none' result=" . $row['result'] . "></span>";
  $view_status[$i][4] = "<span class='hidden' style='display:none' result=" . $row['result'] . "></span>";
  $view_status[$i][5] = "<span class='hidden' style='display:none' result=" . $row['result'] . "></span>";
  if (intval($row['result']) == 11 && ((isset($_SESSION[$OJ_NAME . '_' . 'user_id']) && $row['user_id'] == $_SESSION[$OJ_NAME . '_' . 'user_id']) || isset($_SESSION[$OJ_NAME . '_' . 'source_browser']))) {
    $view_status[$i][3] .= "<a href=ceinfo.php?sid=" . $row['solution_id'] . " class='" . $judge_color[$row['result']] . "' title='$MSG_Tips'>" . $MSG_Compile_Error . "</a>";
    $view_status[$i][4] .= "<span class='label label-success'>0%</span>";
    $view_status[$i][5] .= "<span>---</span>";
  } else if ((((intval($row['result']) == 8 || intval($row['result']) == 7 || intval($row['result']) == 5 || intval($row['result']) == 6) && ($OJ_SHOW_DIFF || isset($_SESSION[$OJ_NAME . '_' . 'source_browser']))) || $row['result'] == 10 || $row['result'] == 13) && ((isset($_SESSION[$OJ_NAME . '_' . 'user_id']) && $row['user_id'] == $_SESSION[$OJ_NAME . '_' . 'user_id']) || isset($_SESSION[$OJ_NAME . '_' . 'source_browser']))) {
    $view_status[$i][3] .= "<a href=reinfo.php?sid=" . $row['solution_id'] . " class='" . $judge_color[$row['result']] . "' title='$MSG_Tips'>" . $judge_result[$row['result']] . $mark . "</a>";
    $view_status[$i][4] .= "<span class='label label-success'>0%</span>";
    $view_status[$i][5] .= "<span>---</span>";
  } else {
    if (!$lock || $lock_time > $row['in_date'] || $row['user_id'] == $_SESSION[$OJ_NAME . '_' . 'user_id']) {
      if ($OJ_SIM && $row['sim'] > 80 && $row['sim_s_id'] != $row['s_id']) {
        $view_status[$i][3] .= "<a href=reinfo.php?sid=" . $row['solution_id'] . " class='" . $judge_color[$row['result']] . "' title='$MSG_Tips'>*" . $judge_result[$row['result']];

        if ($row['result'] != 4 && isset($row['pass_rate']) && $row['pass_rate'] != 1)
          $view_status[$i][3] .= $mark . "</a>";
        else
          $view_status[$i][3] .= "</a>";
        if (intval($row['sim']) >= 100) {
          $view_status[$i][4] .= "<a href=comparesource.php?left=" . $row['sim_s_id'] . "&right=" . $row['solution_id'] . " class='label label-danger' style='margin-left:1px;margin-right:1px;' target=original>" . $row['sim'] . "%</a>";
        } else {
          $view_status[$i][4] .= "<a href=comparesource.php?left=" . $row['sim_s_id'] . "&right=" . $row['solution_id'] . " class='label label-warning' style='margin-left:1px;margin-right:1px;' target=original>" . $row['sim'] . "%</a>";
        }
        $query_result = pdo_query("SELECT user_id,nick FROM `solution` JOIN `sim` ON `sim`.`sim_s_id`=`solution`.`solution_id` WHERE `sim`.`sim_s_id` = ?", $row['sim_s_id'])[0];
        $view_status[$i][5] .= "<a class='label label-default' href='userinfo.php?user=" . $query_result[0] . "'>" . $query_result[0] . " " . $query_result[1] . "</a>";
      } else {
        $view_status[$i][3] .= "<a href=reinfo.php?sid=" . $row['solution_id'] . " class='" . $judge_color[$row['result']] . "' title='$MSG_Tips'>" . $judge_result[$row['result']] . $mark . "</a>";
        $view_status[$i][4] .= "<span class='label label-success'>0%</span>";
        $view_status[$i][5] .= "<span>---</span>";
      }
    } else {
      $view_status[$i][3] = "----";
    }
  }

  if (isset($_SESSION[$OJ_NAME . '_' . 'http_judge'])) {
    $view_status[$i][3] .= "<form class='http_judge_form form-inline'> <input type=hidden name=sid value='" . $row['solution_id'] . "'>";
    $view_status[$i][3] .= "</form>";
  }

  if ($flag) {
    if ($row['result'] >= 4) {
      $view_status[$i][6] = "<div id=center>" . $row['memory'] . " KB</div>";
      $view_status[$i][7] = "<div id=center>" . $row['time'] . " ms</div>";
      //echo "=========".$row['memory']."========";
    } else {
      $view_status[$i][6] = "---";
      $view_status[$i][7] = "---";
    }
    if (!isset($end_time)) {
      $end_time = time();
    }
    //echo $row['result'];
    if (!(isset($_SESSION[$OJ_NAME . '_' . 'user_id']) && strtolower($row['user_id']) == strtolower($_SESSION[$OJ_NAME . '_' . 'user_id'])
      || isset($_SESSION[$OJ_NAME . '_' . 'source_browser']))) {
      $view_status[$i][6] = $language_name[$row['language']];
    } else {
      if (
        time() < $end_time
        || (isset($_SESSION[$OJ_NAME . '_' . 'user_id']) && strtolower($row['user_id']) == strtolower($_SESSION[$OJ_NAME . '_' . 'user_id']))
        || isset($_SESSION[$OJ_NAME . '_' . 'source_browser'])
      )
        $view_status[$i][8] = "<a target=_self href=showsource.php?id=" . $row['solution_id'] . ">" . $language_name[$row['language']] . "</a>";
      else
        $view_status[$i][8] = $language_name[$row['language']];

      if ($row["problem_id"] > 0) {
        if ($row['contest_id'] > 0) {
          if (time() < $end_time || isset($_SESSION[$OJ_NAME . '_' . 'source_browser']))
            $view_status[$i][8] .= "/<a target=_self href=\"submitpage.php?cid=" . $row['contest_id'] . "&pid=" . $row['num'] . "&sid=" . $row['solution_id'] . "\">Edit</a>";
          else
            $view_status[$i][8] .= "";
        } else {
          $view_status[$i][8] .= "/<a target=_self href=\"submitpage.php?id=" . $row['problem_id'] . "&sid=" . $row['solution_id'] . "\">Edit</a>";
        }
      }
    }

    $view_status[$i][9] = $row['code_length'] . " Bytes";
  } else {
    $view_status[$i][6] = "---";
    $view_status[$i][7] = "---";
    $view_status[$i][8] = "---";
    $view_status[$i][9] = "---";
  }

  $rs = (strtotime($row['judgetime']) - strtotime($row['in_date']));
  if ($rs >= 100)
    $rs = "-";
  $view_status[$i][10] = $row['in_date'] . "[" . $rs . "]";

  $view_status[$i][11] = $row['judger'];



  if (isset($gid)) {
    $view_status[$i][12] = $group_name;
  } else {
    $user_id = $row['user_id'];
    $sql_group = "SELECT `name` FROM `users` JOIN `group` ON `users`.gid = `group`.gid WHERE `users`.`user_id` = $user_id;";
    $view_status[$i][12] = "";
    if (isset(pdo_query($sql_group)[0]["name"])) $view_status[$i][12] = pdo_query($sql_group)[0]["name"];
  }
}

?>

<?php

if (isset($cid))
  require("template/conteststatusadmin.php");
else
  require("template/statusadmin.php");

if (file_exists('./include/cache_end.php'))
  require_once('./include/cache_end.php');
?>
