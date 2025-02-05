<!DOCTYPE html>
<html lang="<?php echo $OJ_LANG ?>">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="<?php echo $OJ_NAME ?>">
    <link rel="shortcut icon" href="/favicon.ico">

    <title><?php echo $OJ_NAME ?></title>
    <?php include("template/css.php"); ?>



</head>

<body>

    <div class="container">
        <?php include("template/nav.php"); ?>
        <!-- Main component for a primary marketing message or call to action -->
        <div class="jumbotron" style='padding:20px;'>
            <div class='row'>
                <div class='col-md-4'>
                    <center>
                        <table class="table table-striped" id=statics width=70%>
                            <thead>
                                <tr>
                                    <th colspan='2'>
                                        <div style='font-size:140%;'>
                                            <?php echo htmlentities($nick, ENT_QUOTES, "UTF-8") ?>
                                        </div>
                                        <div>
                                            <?php echo $user; ?>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td width=20%><?php echo $MSG_Number ?></td>
                                    <td width=80% align=center><?php echo $Rank ?></td>
                                </tr>
                                <tr>
                                    <td><?php echo $MSG_SOVLED ?>
                                    <td align=center><a href='status.php?user_id=<?php echo $user ?>&jresult=4'><?php echo $AC ?></td></a>

                                </tr>
                                <tr>
                                    <td><?php echo $MSG_SUBMIT ?></td>
                                    <td align=center><a href='status.php?user_id=<?php echo $user ?>'><?php echo $Submit ?></a></td>
                                </tr>
                                <?php
                                foreach ($view_userstat as $row) {
                                    //i++;
                                    echo "<tr><td>" . $jresult[$row[0]] . "</td><td align=center><a href=status.php?user_id=$user&jresult=" . $row[0] . " >" . $row[1] . "</a></td></tr>";
                                }
                                //}
                                ?>
                                <tr id='pie'>
                                    <td><?php echo $MSG_STATISTICS ?></td>
                                    <td style='height:150px;width:80%'>
                                        <div id='container_pie' style='height:100%;width:100%;'></div>
                                    </td>
                                </tr>
                                <?php if ($group_name) { ?>
                                    <tr>
                                        <td><?php echo $MSG_GROUP ?></td>
                                        <td align=center><?php echo $group_name ?></td>
                                    </tr>
                                <?php } ?>
                                <?php if ($school) { ?>
                                    <tr>
                                        <td><?php echo $MSG_SCHOOL ?></td>
                                        <td align=center><?php echo $school ?></td>
                                    </tr>
                                <?php } ?>
                                <?php if (count($view_userinfo)) { ?>
                                    <tr>
                                        <td><?php echo $MSG_IP_LOCATION ?></td>
                                        <?php
                                        require_once("./include/iplocation.php");
                                        $user_ip = $view_userinfo[0][2];
                                        $info = getLocationShort($user_ip);
                                        ?>
                                        <td align=center><?php echo $info ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </center>
                </div>
                <div class='col-md-8'>
                    <center>
                        <table class="table table-striped" id='submission' width=70%>
                            <thead>
                                <tr>
                                    <th style='text-align:center;width:80%;'><?php echo $MSG_SOVLED ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td rowspan=14 align=center>
                                        <script language='javascript'>
                                            function p(id, c) {
                                                document.write("<a href=problem.php?id=" + id + ">" + id + " </a>(<a href='status.php?user_id=<?php echo $user ?>&problem_id=" + id + "'>" + c + "</a>)&nbsp;&nbsp;");

                                            }
                                            <?php $sql = "SELECT `problem_id`,count(1) from solution where `user_id`=? and result=4 group by `problem_id` ORDER BY `problem_id` ASC";
                                            if ($result = pdo_query($sql, $user)) {
                                                foreach ($result as $row)
                                                    echo "p($row[0],$row[1]);";
                                            }
                                            ?>
                                        </script>
                                        <div id="container_status" style="height:300px;margin:1em auto 1em"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </center>
                </div>
            </div>
            <center>
                <br>
                <?php
                if (isset($_SESSION[$OJ_NAME . '_' . 'administrator'])) {
                ?>
                    <div class='table-responsive'>
                        <table class='table table-condensed' style='width:auto'>
                            <thead>
                                <tr>
                                    <th style='text-align:center;'>UserID</th>
                                    <th style='text-align:center;'><?php echo $MSG_STATUS ?></th>
                                    <th style='text-align:center;'>IP</th>
                                    <th style='text-align:center;'>Time</th>
                                    <th style='text-align:center;'><?php echo $MSG_IP_LOCATION ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $cnt = 0;
                                require_once("./include/iplocation.php");
                                foreach ($view_userinfo as $row) {
                                    if ($cnt)
                                        echo "<tr class='oddrow'>";
                                    else
                                        echo "<tr class='evenrow'>";
                                    for ($i = 0; $i < count($row) / 2; $i++) {
                                        echo "<td style='text-align:center;'>";
                                        echo "\t" . $row[$i];
                                        echo "</td>";
                                    }
                                    $info = getLocationFull($row[2]);
                                    echo "<td style='text-align:center;'>"
                                        . $info
                                        . "</td>";
                                    echo "</tr>";
                                    $cnt = 1 - $cnt;
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                <?php
                }
                ?>
            </center>
        </div>

    </div>
    <?php include("template/js.php"); ?>
    </script>
    <script src="<?php echo $OJ_CDN_URL . "template/" ?>echarts.min.js"></script>
    <script>
        var statusChart = echarts.init(document.getElementById('container_status'));
        var statusOption = {
            title: {
                text: "<?php echo $MSG_RECENT_SUBMISSION ?>",
            },
            legend: [{
                data: ['<?php echo $MSG_TOTAL ?>', '<?php echo $MSG_ACCEPTED ?>'],
                top: "10%"
            }],
            grid: {
                left: '1%',
                right: '1%',
                bottom: '1%',
                containLabel: true
            },
            tooltip: {
                trigger: 'axis',
                formatter: function(params) {
                    var text = '--'
                    if (params && params.length) {
                        text = params[0].data[0]
                        params.forEach(item => {
                            var dotHtml = item.marker
                            text += `<div style='text-align:left'>${dotHtml}${item.seriesName} : ${item.data[1] ? item.data[1] : '-'}</div>`
                        })
                    }
                    return text
                }
            },
            xAxis: {
                type: 'time',
            },
            yAxis: {
                type: 'value'
            },
            textStyle: {
                fontFamily: "Inter-SemiBold,SourceHanSansCN-Medium"
            },
            series: [{
                data: <?php echo json_encode($chart_data_all) ?>,
                type: 'line',
                name: '<?php echo $MSG_TOTAL ?>',
                color: '#4B4B4B',
                smooth: true
            }, {
                data: <?php echo json_encode($chart_data_ac) ?>,
                type: 'line',
                name: '<?php echo $MSG_ACCEPTED ?>',
                color: '#22D35E',
                smooth: true
            }]
        };
        statusChart.setOption(statusOption);

        var info = new Array();
        var dt = document.getElementById("statics");
        var data = dt.rows;
        var n;
        var m;
        var rate;
        var total = parseInt(dt.rows[3].cells[1].innerText);
        for (var i = 4; dt.rows[i].id != "pie"; i++) {
            n = dt.rows[i].cells[0];
            n = n.innerText || n.textContent;
            m = dt.rows[i].cells[1].firstChild;
            m = m.innerText || m.textContent;
            m = parseInt(m);
            rate = Math.round(m / total * 1000) / 10;
            info.push({
                name: n + ` (${rate}%)`,
                value: m
            });
        }
        var pieChart = echarts.init(document.getElementById('container_pie'));
        var pieOption = {
            grid: {
                left: '1%',
                right: '1%',
                bottom: '1%',
                containLabel: true
            },
            tooltip: {
                trigger: 'item'
            },
            textStyle: {
                fontFamily: "Inter-SemiBold,SourceHanSansCN-Medium"
            },
            series: [{
                radius: ["40%", "80%"],
                itemStyle: {
                    borderRadius: 10,
                    borderColor: '#fff',
                    borderWidth: 2
                },
                type: 'pie',
                data: info
            }]
        };
        pieChart.setOption(pieOption);
        window.onresize = function() {
            statusChart.resize();
            pieChart.resize();
        };
    </script>
</body>

</html>