<?php

    include("config.php");

    // Check if code is in database (in case user changes code in url bar!)
    $sql = "SELECT CODE FROM DATASET WHERE CODE= ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $_GET['code']);
    $stmt->execute();
    $result = $stmt->get_result();
    $codeMatch = $result->num_rows;
    if($codeMatch != 1){
        echo "<h2 style='text-align:center; color:red'>Dataset does not exist</h2>";
        exit();
    } else {

        $query = "SELECT ENT1.TERM_ID AS TERM1, ENT1.NAME AS ENTITY1, ENT2.TERM_ID AS TERM2, ENT2.NAME AS ENTITY2, USER.FIELD, VALUE, DATE FROM SIMILARITY_VALUE JOIN ENTITY ENT1 ON SIMILARITY_VALUE.ID_ENTITY1 = ENT1.ID JOIN ENTITY ENT2 ON SIMILARITY_VALUE.ID_ENTITY2 = ENT2.ID JOIN USER ON SIMILARITY_VALUE.ID_USER = USER.ID WHERE DATASET_CODE = ?";
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, "s", $_GET['code']);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

    }


?>

<!DOCTYPE html>
<html>
    <head>
    <style>

        body{ font: 14px sans-serif; text-align: center; 
            background: linear-gradient(90deg, rgb(131, 215, 236) -50%, rgba(238, 236, 236, 0.103) 40%, rgb(131, 215, 236) 220%);
        }

        .title{ 
            background-color: #251488; 
            color: #ffffff; 
            padding: 15px; 
            box-shadow: 0px 2px 10px #251488;
        }
        
        .cont {
            text-align: center;
        }

        .table_bordered {
            border: 1px solid black; 
            width: 80%;
            margin-left: 10%; /* if the table is at a certain percentage width we use margin with the remaining percentage to center it*/
            margin-right: 10%;
        }

        th {
            height: 60px;
            text-align:center;
            padding: 10px;
        }

        td{
            height: 30px;
            vertical-align: bottom;
            text-align:left;
            padding: 10px;
        }

        th, td {
            padding: 8px;
            /* text-align: left; */
            border-bottom: 1px solid #ddd;
        }

    </style>
    <title>Download manual values</title>
    </head>

    <body>
        <div class="title">
            <h1>Manual Validation Helper Tool</h1>
        </div>
        <div class = "cont">
            <h2>Results for dataset <?php echo $_GET['code'];?> </h2>
            <form method ="post" action="export_values.php">
                <input type="hidden" name ="dataset_code" value=<?php echo $_GET['code'];?>>
                <input type = "submit" name = "export" value = "Export(CSV)">
            </form>
        </div>
        </br>
        <div class= "table_responsive" id = "similarity_value_table" style = "overflow-x:auto;">
            <table class = "table_bordered">
                <tr>
                    <th width="5%"> Term ID 1</th>
                    <th width="15%"> Name 1 </th>
                    <th width="5%"> Term ID 2</th>
                    <th width="15%"> Name 2 </th>
                    <th width="5%"> Field</th>
                    <th width="5%"> Similarity Value</th>
                    <th width="10%"> Date</th>
                </tr>
            <?php
            while($row = mysqli_fetch_assoc($result)) 
            {
            ?>
                <tr>
                    <td><?php echo $row["TERM1"]; ?></td>
                    <td><?php echo $row["ENTITY1"]; ?></td>
                    <td><?php echo $row["TERM2"]; ?></td>
                    <td><?php echo $row["ENTITY2"]; ?></td>
                    <td><?php echo $row["FIELD"]; ?></td>
                    <td><?php echo $row["VALUE"]; ?></td>
                    <td><?php echo $row["DATE"]; ?></td>
                </tr>
            <?php
            }
            ?>
            </table>
        </div>
    </body>
</html>
