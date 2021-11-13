<?php
    session_start();

    header('Cache-Control: private, max-age=3600, no-cache');
    header('Expires: ' . date('D, d M Y H:i:s \G\M\T', time() + 60 * 60)); // 1 hour from now


    include("config.php");


    $_SESSION['code'] = $_GET['code'];

    $user_id = $_SESSION['userid'];

    // Check if a user inserted its data previously, if not then redirects user back
    if(!isset($_SESSION['userid'])){
        header("Location: http://mvht.lasige.di.fc.ul.pt/insert_users.php?code=".$_GET['code']."");
        // http://mvht.lasige.di.fc.ul.pt/insert_users.php?code=
        // http://localhost/projects/kegg%20files/insert_users.php?code=
        exit();
    }


    //.........................................................................................
    $dataset_code = $_GET['code'];

    //Query that gets the type of PAIR_SELECTION column from that dataset table
    $sql = "SELECT PAIR_SELECTION FROM DATASET WHERE CODE=?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $dataset_code);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // fetch an array of rows
    $selection_type = $result[0]['PAIR_SELECTION']; // this value can be either abrangent or redundant
    $stmt ->close();


    //Query that gets all the pairs from table PAIR into an array variable
    $sql = "SELECT ID_ENT1, ID_ENT2 FROM PAIR INNER JOIN DATASET ON DATASET.ID = PAIR.ID_DS WHERE DATASET.CODE = ?";
    $stmt = $con -> prepare($sql);
    $stmt -> bind_param("s", $dataset_code);
    //set parameters and execute
    $stmt -> execute();
    $pairs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // fetch an array of rows
    $stmt ->close();



    $num1 = 0;
    $num2 = 0;
    $pair_array = array();

    
    //this condition checks if the $selection_type value is random and gives two totally random but not equal entities
    if ($selection_type == "random") { 

        $sql = "SELECT MIN(ENTITY.ID) AS minid, MAX(ENTITY.ID) AS maxid FROM ENTITY INNER JOIN DATASET ON ENTITY.DATASET_ID = DATASET.ID WHERE DATASET.CODE = '{$_GET['code']}'";

        $query = mysqli_query($con, $sql);
        $entityCount = mysqli_fetch_assoc($query);

        $randomNumber1 = rand($entityCount['minid'], $entityCount['maxid']);
        do {
            $randomNumber2 = rand($entityCount['minid'], $entityCount['maxid']) or die("That dataset does not exist");
        } while ($randomNumber1 === $randomNumber2);


        $sql1 = "SELECT ID FROM ENTITY WHERE ID = {$randomNumber1}";
        $result = mysqli_query($con, $sql1);

        if (mysqli_num_rows($result) > 0) {
        //output data for each row
            while ($row = mysqli_fetch_assoc($result)){
                $num1 = $row['ID'];
            }
        }

        $sql2 = "SELECT ID FROM ENTITY WHERE ID = {$randomNumber2}";
        $result = mysqli_query($con, $sql2);

        if (mysqli_num_rows($result) > 0) {
        //output data for each row
            while ($row = mysqli_fetch_assoc($result)){
                $num2 = $row['ID'];
            }
        }

    } elseif($selection_type == "abrangent") { //this condition checks if the $selection_type value is abrangent and gives the adjacent pair to the last pair that got an answer

        //Query that gets the last row of inserted into table SIMILARITY_VALUES
        $sql2 = "SELECT ID_ENTITY1 AS 'ENT1', ID_ENTITY2 AS 'ENT2' FROM SIMILARITY_VALUE WHERE SIMILARITY_VALUE.DATASET_CODE = ? ORDER BY ID DESC LIMIT 1";
        $stmt2 = $con -> prepare($sql2);
        $stmt2 -> bind_param("s", $dataset_code);

        //set parameters and execute
        $stmt2 -> execute();
        $sim_values = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC); // fetch an array of rows
        $stmt2 -> close();
        
        //pair_match variable that becomes TRUE when it matches the last row in similarity_values table with the current pair in the for loop, and gives that pair
        $pair_match = FALSE;
        if(!empty($sim_values)){
            //checks it the last comparison made matches with the last pair in the generated pairs table
            if($sim_values[0]['ENT1'] == end($pairs)['ID_ENT1'] && $sim_values[0]['ENT2'] == end($pairs)['ID_ENT2'] || $sim_values[0]['ENT1'] == end($pairs)['ID_ENT2'] && $sim_values[0]['ENT2'] == end($pairs)['ID_ENT1']) {
                $sql1 = "SELECT ID FROM ENTITY WHERE ID = {$pairs[0]['ID_ENT1']}";
                $result = mysqli_query($con, $sql1);

                if (mysqli_num_rows($result) > 0) {
                //output data for each row
                    while ($row = mysqli_fetch_assoc($result)){
                        $num1 = $row['ID'];
                    }
                }

                $sql2 = "SELECT ID FROM ENTITY WHERE ID = {$pairs[0]['ID_ENT2']}";
                $result = mysqli_query($con, $sql2);

                if (mysqli_num_rows($result) > 0) {
                //output data for each row
                    while ($row = mysqli_fetch_assoc($result)){
                        $num2 = $row['ID'];
                    }
                }
            } else {

                foreach ($pairs as $key => $pair) {
                    $pair_array = array($pair['ID_ENT1'], $pair['ID_ENT2']);
                    
                    if($pair_match == TRUE) {
                        // echo $pair_array[0] . "----" . $pair_array[1] . "<br>";

                        $sql1 = "SELECT ID FROM ENTITY WHERE ID = {$pair_array[0]}";
                        $result = mysqli_query($con, $sql1);

                        if (mysqli_num_rows($result) > 0) {
                        //output data for each row
                            while ($row = mysqli_fetch_assoc($result)){
                                $num1 = $row['ID'];
                            }
                        }

                        $sql2 = "SELECT ID FROM ENTITY WHERE ID = {$pair_array[1]}";
                        $result = mysqli_query($con, $sql2);

                        if (mysqli_num_rows($result) > 0) {
                        //output data for each row
                            while ($row = mysqli_fetch_assoc($result)){
                                $num2 = $row['ID'];
                            }
                        }

                        $pair_match = FALSE;
                    }

                    foreach ($sim_values as $key => $sim_value) {
                        if(in_array($sim_value['ENT1'], $pair_array) && in_array($sim_value['ENT2'], $pair_array)) {
                            // $pair_array = array();
                            $pair_match = TRUE;
                        }
                    }
                }
            }
        } else { //If the array is empty then it gives te first pair of the pair array, the next one will not enter this else condition
            
            //Query that gets all the pairs from table PAIR into an array variable
            $sql = "SELECT ID_ENT1, ID_ENT2 FROM PAIR INNER JOIN DATASET ON DATASET.ID = PAIR.ID_DS WHERE DATASET.CODE = ?";
            $stmt = $con -> prepare($sql);
            // echo $dataset_code ."<br>";
            $stmt -> bind_param("s", $dataset_code);
            //set parameters and execute
            $stmt -> execute();
            // $result =  $stmt -> get_result(); //get the mysqli result
            $pairs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // fetch an array of rows
            $stmt ->close();

            
            $sql1 = "SELECT ID FROM ENTITY WHERE ID = {$pairs[0]['ID_ENT1']}";
            $result = mysqli_query($con, $sql1);

            if (mysqli_num_rows($result) > 0) {
            //output data for each row
                while ($row = mysqli_fetch_assoc($result)){
                    $num1 = $row['ID'];
                }
            }

            $sql2 = "SELECT ID FROM ENTITY WHERE ID = {$pairs[0]['ID_ENT2']}";
            $result = mysqli_query($con, $sql2);

            if (mysqli_num_rows($result) > 0) {
            //output data for each row
                while ($row = mysqli_fetch_assoc($result)){
                    $num2 = $row['ID'];
                }
            }

        }
    } else { //means $selection_type value is redundant and each time a new user enters the page it will start from the beggining of the pairs generated

        //Query that gets the last row of inserted into table SIMILARITY_VALUES
        $sql2 = "SELECT ID_ENTITY1 AS 'ENT1', ID_ENTITY2 AS 'ENT2', ID_USER AS 'USER' FROM SIMILARITY_VALUE WHERE SIMILARITY_VALUE.DATASET_CODE = ? AND SIMILARITY_VALUE.ID_USER=? ORDER BY ID DESC LIMIT 1";
        $stmt2 = $con -> prepare($sql2);
        $stmt2 -> bind_param("si", $dataset_code, $user_id);

        //set parameters and execute
        $stmt2 -> execute();

        $sim_values_with_user = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt2 -> close();

        $pair_match = FALSE;
        if(!empty($sim_values_with_user)){
            //this checks it the last comparison made matches with the last pair in the generated pairs table and also if the user is the same or not
            if($sim_values_with_user[0]['ENT1'] == end($pairs)['ID_ENT1'] && $sim_values_with_user[0]['ENT2'] == end($pairs)['ID_ENT2'] || $sim_values_with_user[0]['ENT1'] == end($pairs)['ID_ENT2'] && $sim_values_with_user[0]['ENT2'] == end($pairs)['ID_ENT1'] && $sim_values_with_user[0]['USER'] == $user_id) {
                $sql1 = "SELECT ID FROM ENTITY WHERE ID = {$pairs[0]['ID_ENT1']}";
                $result = mysqli_query($con, $sql1);

                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)){
                        $num1 = $row['ID'];
                    }
                }

                $sql2 = "SELECT ID FROM ENTITY WHERE ID = {$pairs[0]['ID_ENT2']}";
                $result = mysqli_query($con, $sql2);

                if (mysqli_num_rows($result) > 0) {

                    while ($row = mysqli_fetch_assoc($result)){
                        $num2 = $row['ID'];
                    }
                }
            } else {

                foreach ($pairs as $key => $pair) {
                    $pair_array = array($pair['ID_ENT1'], $pair['ID_ENT2']);
                    
                    if($pair_match == TRUE) {

                        $sql1 = "SELECT ID FROM ENTITY WHERE ID = {$pair_array[0]}";
                        $result = mysqli_query($con, $sql1);

                        if (mysqli_num_rows($result) > 0) {

                            while ($row = mysqli_fetch_assoc($result)){
                                $num1 = $row['ID'];
                            }
                        }

                        $sql2 = "SELECT ID FROM ENTITY WHERE ID = {$pair_array[1]}";
                        $result = mysqli_query($con, $sql2);

                        if (mysqli_num_rows($result) > 0) {

                            while ($row = mysqli_fetch_assoc($result)){
                                $num2 = $row['ID'];
                            }
                        }

                        $pair_match = FALSE;
                    }

                    foreach ($sim_values_with_user as $key => $sim_value_with_user) {
                        if(in_array($sim_value_with_user['ENT1'], $pair_array) && in_array($sim_value_with_user['ENT2'], $pair_array)) {

                            $pair_match = TRUE;
                        }
                    }
                }
            }
        } else { //If the array is empty then it gives te first pair of the pair array, the next one will not enter this else condition
            
            //Query that gets all the pairs from table PAIR into an array variable
            $sql = "SELECT ID_ENT1, ID_ENT2 FROM PAIR INNER JOIN DATASET ON DATASET.ID = PAIR.ID_DS WHERE DATASET.CODE = ?";
            $stmt = $con -> prepare($sql);
            $stmt -> bind_param("s", $dataset_code);
            //set parameters and execute
            $stmt -> execute();

            $pairs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // fetch an array of rows
            $stmt ->close();
            
            $sql1 = "SELECT ID FROM ENTITY WHERE ID = {$pairs[0]['ID_ENT1']}";
            $result = mysqli_query($con, $sql1);

            if (mysqli_num_rows($result) > 0) {

                while ($row = mysqli_fetch_assoc($result)){
                    $num1 = $row['ID'];
                }
            }

            $sql2 = "SELECT ID FROM ENTITY WHERE ID = {$pairs[0]['ID_ENT2']}";
            $result = mysqli_query($con, $sql2);

            if (mysqli_num_rows($result) > 0) {

                while ($row = mysqli_fetch_assoc($result)){
                    $num2 = $row['ID'];
                }
            }

        }
        
    }
    //-------------------------------------------------------------------------------------------

    //Set session variables for the two numbers in the pair (which are the IDs for each entity being compared)
    $_SESSION['ent1'] = $num1;
    $_SESSION['ent2'] = $num2;


    $sql = "SELECT ANNOTATION.SECTION FROM ANNOTATION INNER JOIN ENTITY ON ENTITY.ID = ANNOTATION.ENTITY_ID INNER JOIN DATASET ON DATASET.ID = ENTITY.DATASET_ID WHERE DATASET.CODE = '{$_GET['code']}' GROUP BY ANNOTATION.SECTION";
    
    $result = mysqli_query($con, $sql);

    $cat = array();
    if (mysqli_num_rows($result) > 0) {

        while ($row = mysqli_fetch_assoc($result)){

            array_push($cat, $row['SECTION']);
        }
    }
    
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Similarity Tool </title>
    <link rel="stylesheet" href="style.css">
    
    <style>
        .grid-container {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            align-content: stretch;
            width: 80%;
            margin:0 auto;
            padding-bottom: 100px;
        }

        .grid-item {
            border: 1px solid black;
            padding: 20px;
            margin-bottom: 5px;
            overflow: auto;
            height: 150px;
            max-height: 300px;

        }


        .static {
            display: grid;
            grid-template-columns: 1fr 100px 1fr;
            justify-items: center;
            justify-content: space-around;
            margin: auto;
            width: 80%;
            background: rgb(9, 2, 43);
            color:white;
            padding: 10px;
            box-shadow: 0px 2px 10px #251488;

        }

        .description {
            display: grid;
            grid-template-columns: 1fr 1fr;
            justify-items:center;
            text-align:center;
            margin: auto;
            width: 80%;
            
        }

        .desc-bar {
            grid-column-start: 1;
            grid-column-end: 3;
            font-size: 16px;
            padding: 10px;
            background-color: gray;
            color: white;
            text-align: center;
            height: 26px;
            width: 100%;
            margin-bottom: 10px;
        }

        div.desc-bar {
            -webkit-transition: height 0.5s;
            transition: height 0.5s;
        }

        .desc-bar:hover {
            background-color: darkcyan;
            cursor: pointer;
            -webkit-transition: background-color 0.5s;
            transition: background-color 0.5s;
        }

        .stick-top {
            background: linear-gradient(90deg, rgb(131, 215, 236) -50%, rgba(238, 236, 236, 1) 40%, rgb(131, 215, 236) 220%);
            position: -webkit-sticky;
            position: sticky;
            top: 0;
        }


        div.line:nth-child(even) {
            background: #CCC
        }

        div.line:nth-child(odd) {
            background: #FFF
        }

        .range-value{
            position: absolute;
            top: -50%;
        }

        .range-value span{
            width: 30px;
            height: 24px;
            line-height: 24px;
            text-align: center;
            background: #03a9f4;
            color: #fff;
            font-size: 12px;
            display: block;
            position: absolute;
            left: 50%;
            transform: translate(-50%, 0);
            border-radius: 6px;
        }

        .range-value span:before{
            content: "";
            position: absolute;
            width: 0;
            height: 0;
            border-top: 10px solid rgb(9, 2, 43);
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            top: 100%;
            left: 50%;
            margin-left: -2px;
            margin-top: 3px;
        }

    </style>
</head>

<body>

<div class="stick-top">
    <img src="1-512.png" alt="venn diagram" style="width:100px; height:80px; position:absolute; top: 0px; left:10px; z-index:1"><p style="position:absolute; top:55px; left:38px; z-index:1"><b>MVHT</b></p>
    <div class="static" >
        <div style="display:inline-block; padding: 5px;">
            <?php
                $sqlname = "SELECT NAME, TERM_ID, LINK FROM ENTITY WHERE ID = {$num1}";
                $resultname = mysqli_query($con, $sqlname);
                $row = mysqli_fetch_assoc($resultname);
                ?><h4> <a id="link" href="<?php echo $row['LINK'];?>"><?php echo $row['NAME']; ?></a></h4> <?php
            ?>
        </div>
        <div style="display:inline-block" ><h1>VS</h1></div>
        <div style="display:inline-block; padding: 5px;">
            <?php
                $sqlname2 = "SELECT NAME, TERM_ID, LINK FROM ENTITY WHERE ID = {$num2}";
                $resultname2 = mysqli_query($con, $sqlname2);
                $row = mysqli_fetch_assoc($resultname2);
                ?><h4><a id="link"href="<?php echo $row['LINK'];?>"><?php echo $row['NAME']; ?></a></h4> <?php
            ?>
        </div>
    </div>
</div>

<div class="centerme">
    <div class = "description">
            <div class = "desc-bar" onclick="showhideDesc('desc1', 'desc2')">
                <img id="expandCollapse" src="iconfinder_icon-arrow-up-b_211623.png" width = "30" height="30" style="float:left;">
                <p> Description </p>
            </div>
            <div id = "desc1" class="fixed-panel">
                <?php
                    $sqldesc = "SELECT ENTITY.DESCRIPTION FROM ENTITY WHERE ID = {$num1}";
                    $resultdesc = mysqli_query($con, $sqldesc);
                    $row = mysqli_fetch_assoc($resultdesc);
                    ?><p><?php echo $row['DESCRIPTION']; ?></p> <?php
                ?>
            </div>
            <div id = "desc2" class = "fixed-panel">
                <?php
                    $sqldesc2 = "SELECT ENTITY.DESCRIPTION FROM ENTITY WHERE ID = {$num2}";
                    $resultdesc2 = mysqli_query($con, $sqldesc2);
                    $row = mysqli_fetch_assoc($resultdesc2);
                    ?><p><?php echo $row['DESCRIPTION']; ?></p> <?php
                ?>
            </div>
        </div>

    <div class = "grid-container">
    <?php
    foreach ($cat as $key => $value) {
        // For entity 1
        $sql = "SELECT DISTINCT ANNOTATION.NAME, ANNOTATION.TERM_ID, ANNOTATION.LINK FROM ANNOTATION INNER JOIN ENTITY ON ENTITY.ID = ANNOTATION.ENTITY_ID
        WHERE ENTITY_ID = {$num1} AND SECTION = '{$value}' ORDER BY ANNOTATION.NAME ASC";
        $result = mysqli_query($con, $sql);

        // For entity 2
        $sql2 = "SELECT DISTINCT ANNOTATION.NAME, ANNOTATION.TERM_ID, ANNOTATION.LINK FROM ANNOTATION INNER JOIN ENTITY ON ENTITY.ID = ANNOTATION.ENTITY_ID
        WHERE ENTITY_ID = {$num2} AND SECTION = '{$value}' ORDER BY ANNOTATION.NAME ASC";
        $result2 = mysqli_query($con, $sql2);

        //IDs may have two separated words i.e. 'anatomical entities' and this is not possible,
        //so where there are spaces we insert an underscore using preg_replace
        $value = preg_replace("/\s/", "_", $value);

        $result_array = array();
        $result_array2 = array();

        // For entity 1
        if (mysqli_num_rows($result) > 0) {

            while ($row = mysqli_fetch_assoc($result)){
                $temp_array = array('name' => $row['NAME'], 'id' => $row['TERM_ID'], 'link' => $row['LINK']);
                array_push($result_array, $temp_array);
                
            }
        }
        
        // For entity 2
        if (mysqli_num_rows($result2) > 0) {

            while ($row = mysqli_fetch_assoc($result2)){
                $temp_array = array('name' => $row['NAME'], 'id' => $row['TERM_ID'], 'link' => $row['LINK']);
                array_push($result_array2, $temp_array);
                
            }
        }

        // creates html elements to put annotations inside
        ?> 
        <div id="<?php echo $value;?>" class = "category" onclick="showhide('icon_<?php echo $value; ?>', '<?php echo $value; ?>1', '<?php echo $value; ?>2', 'sim_<?php echo $value; ?>')">
            <img id="icon_<?php echo $value;?>" src="iconfinder_icon-arrow-down-b_211614.png" width = "30" height="30" style="float:left; margin:2% 0%;">
            <p> <?php echo $value; ?> </p>
            <span id="num_ent1_<?php echo $value; ?>" style="margin-left:200px"></span>
            <img src="noun_Venn Diagram_934159.png"  class="venn" alt="venn diagram" style="width:38px; height:32px; float:left;">
            <img src="1-512.png"  class="venn" alt="venn diagram" style="width:30px; height:25px;">
            <span id="num_common_<?php echo $value; ?>"></span>
            <span id="num_ent2_<?php echo $value; ?>" style="margin-right:200px"></span>
            <img src="noun_Venn Diagram_934158.png"  class="venn" alt="venn diagram" style="width:38px; height:32px; float:right">
        </div>

        <!-- For entity 1 -->
        <div id="<?php echo $value; ?>1" class = "grid-item">
        <?php
        $commonAnnotations = array(); // array variable that will store common entries
        for ($i=0; $i < count($result_array); $i++) { 
            for ($j=0; $j < count($result_array2); $j++) { 
                if ($result_array[$i] === $result_array2[$j] && !in_array($result_array[$i], $commonAnnotations)) {
                    array_push($commonAnnotations, $result_array[$i]);
                }
            }
        }

        foreach ($result_array as $key => $ann) {
            if (!in_array($ann, $commonAnnotations)) {
                ?> <div class="line"> <a href="<?php echo $ann['link'];?>"> <?php echo $ann['name']; ?> </a></div><?php
            }
        }
        
        ?>
        </div>
        <div id="sim_<?php echo $value; ?>" class = "grid-item">
        <?php
        // Gives common annotations between entities, if any
        for ($i=0; $i < count($result_array); $i++) { 

            for ($j=0; $j < count($result_array2); $j++) { 
                if($result_array[$i] === $result_array2[$j]) {

                    ?> <div class="line"><a href="<?php echo $result_array[$i]['link'];?>"><?php echo $result_array[$i]['name']; ?></a></div> <?php
                }
            }
        }

        ?>
        </div>
        
        <!-- For entity 2 -->
        <div id="<?php echo $value; ?>2" class = "grid-item">
        <?php
        $commonAnnotations = array(); // array variable that will store common entries
        for ($i=0; $i < count($result_array2); $i++) { 
            for ($j=0; $j < count($result_array); $j++) { 
                if ($result_array2[$i] === $result_array[$j] && !in_array($result_array2[$i], $commonAnnotations)) {
                    array_push($commonAnnotations, $result_array2[$i]);
                }
            }
        }

        foreach ($result_array2 as $key => $ann) {
            if (!in_array($ann, $commonAnnotations)) {
                ?> <div class="line"> <a href="<?php echo $ann['link'];?>"><?php echo $ann['name']; ?></a></div><?php
            }
        }
        ?>
        </div>
    <?php
    }
    ?>
    </div>
    </div>

    <div class = "simform" style="text-align:center;">
        <div>
        <?php
            $sqlname = "SELECT QUESTION FROM DATASET INNER JOIN ENTITY ON DATASET.ID = ENTITY.DATASET_ID WHERE ENTITY.ID = {$num1}";
            $resultname = mysqli_query($con, $sqlname);
            $row = mysqli_fetch_assoc($resultname);
            ?><h3><?php echo $row['QUESTION']; ?></h3> <?php
        ?>
        </div>
        <form action="process.php" method="POST">
            <div class="slideContainer">
                <input type="range" min="0" max="100" value="50" class="slider" id="myRange" name="rangeSlider" onclick="showPopup()">
                <div class="range-value" id="rangeV">

                </div>
            </div>
            <br>
            <!-- <input type="submit" class="submitButton" name="formSubmit" value="Submit"> -->
        </form>
    </div>

    <script src="main.js"></script>
    <script type="text/javascript">

        showhideDesc('desc1', 'desc2'); //show the description does not show when page opens
        <?php
        foreach ($cat as $key => $thing) {

            $thing = preg_replace("/\s/", "_", $thing);

            ?> showhide('icon_<?php echo $thing; ?>', '<?php echo $thing; ?>1', '<?php echo $thing; ?>2', 'sim_<?php echo $thing; ?>');

                //Counts the number of common elements for each category
                var common = document.getElementById("sim_<?php echo $thing; ?>");
                var numberChildren = common.childElementCount; //this gives the number o child elements, since each of them already have an element <p> inside saying "common" we substract one the the total
            
                var entCount1 = document.getElementById("<?php echo $thing; ?>1");
                var numberChildrenEnt1 = entCount1.childElementCount;

                var entCount2 = document.getElementById("<?php echo $thing; ?>2");
                var numberChildrenEnt2 = entCount2.childElementCount;

                //for the part before the icon
                document.getElementById("num_common_<?php echo $thing; ?>").innerHTML = numberChildren;
                document.getElementById("num_common_<?php echo $thing; ?>").style.verticalAlign = "6px";

                //after icon
                document.getElementById("num_ent1_<?php echo $thing; ?>").innerHTML = numberChildrenEnt1;
                document.getElementById("num_ent1_<?php echo $thing; ?>").style.verticalAlign = "6px";
                document.getElementById("num_ent1_<?php echo $thing; ?>").style.cssFloat = "left";

                document.getElementById("num_ent2_<?php echo $thing; ?>").innerHTML = numberChildrenEnt2;
                document.getElementById("num_ent2_<?php echo $thing; ?>").style.verticalAlign = "20px";
                document.getElementById("num_ent2_<?php echo $thing; ?>").style.cssFloat = "right";


                //hide elements where both entities have 0 entries for that category
                hideEmptyElements(numberChildrenEnt1, numberChildrenEnt2, "<?php echo $thing;?>");



            <?php
        }
        ?>

        const
            range = document.getElementById('myRange'),
            rangeV = document.getElementById('rangeV'),
            setValue = ()=>{
                const
                    newValue = Number( (range.value - range.min) * 100 / (range.max - range.min) ),
                    newPosition = 10 - (newValue * 0.2);
                rangeV.innerHTML = `<span>${range.value}</span>`;
                rangeV.innerHTML = `<button name="formSubmit" id="formSubmitButton" type="submit" class="submitButton">
                        Submit!
                    </button>`;
                rangeV.style.left = `calc(${newValue}% + (${newPosition}px))`;
            };
        document.addEventListener("DOMContentLoaded", setValue);
        range.addEventListener('input', setValue);
    </script>
</body>
</html>
