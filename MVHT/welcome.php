<?php
// Initialize the session
session_start();

header('Cache-Control: private, max-age=3600, no-cache');
header('Expires: ' . date('D, d M Y H:i:s \G\M\T', time() + 60 * 60)); // 1 hour from now


require_once("config.php");
 
// Check if the user is logged in, if not then redirect him to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: index.php");
    exit;
}

$error = "";

// Target directory and its file name
$target_dir = "uploads/";
// $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);

if(isset($_POST['submit'])){

    if(!empty($_FILES['fileToUpload']['name'])){

        //File properties
        $file_name = $_FILES['fileToUpload']['name'];
        $file_size =$_FILES['fileToUpload']['size'];
        $file_tmp =$_FILES['fileToUpload']['tmp_name'];
        $file_type=$_FILES['fileToUpload']['type'];
        $file_ext= explode('.',$_FILES['fileToUpload']['name']);
        $file_ext = strtolower(end($file_ext));
        $extensions= array("json");
        
        if(in_array($file_ext,$extensions)=== false){
            $error="extension not allowed, please upload a json file.";
        }

        // Check if file name already exists or not
        $query = "SELECT COUNT(*) AS allcount FROM DATASET WHERE NAME='". $file_name ."'";
        $result = mysqli_query($con, $query);
        $row = mysqli_fetch_array($result);
        $allcount = $row['allcount'];

        //insert new record after checking if there is any dataset with the same name already in the database
        if(empty($error)==true){
            move_uploaded_file($file_tmp,"uploads/".$file_name);
    
            // Prepare an insert statement
            $sql = "INSERT INTO DATASET (NAME, USER_ID, CODE, QUESTION, PAIR_SELECTION) VALUES (?, ?, ?, ?, ?)";
    
            if($stmt = mysqli_prepare($con, $sql)){
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "sisss", $param_name, $param_user_id, $param_code, $param_question, $param_pair_selection);
                
                // Set parameters
                $param_name = $_FILES['fileToUpload']['name'];
                $param_user_id = $_SESSION['id'];
                $param_code = uniqid();

                $fp = fopen('uploads/'.$file_name.'', 'r');

                $ds = fread($fp, filesize('uploads/'.$file_name.''));

                $ds = json_decode($ds);

                $param_question = $ds -> question;

                fclose($fp);

                $abrangent_status = "unchecked";
                $redundant_status = "unchecked";

                // $param_pair_selection = "";
                if(isset($_POST['pairing'])) {

                    $param_pair_selection = $_POST['pairing'];
                }
                
                // Attempt to execute the prepared statement
                if(mysqli_stmt_execute($stmt)){

                    $error= "File uploaded successfully!";

                    // Close statement
                    mysqli_stmt_close($stmt);

                    // Gets the last DATASET id queried so the other dependent tables can use it
                    $datasetId = mysqli_insert_id($con);
                    $_SESSION['dataset'] = $datasetId;

                    $fp = fopen('uploads/'.$file_name.'', 'r');

                    $ds = fread($fp, filesize('uploads/'.$file_name.''));

                    $ds = json_decode($ds);

                    $entities = $ds -> entities;

                    $custom_variable_array = array();

                    for ($i=0; $i < count($entities); $i++) {
                        $array = get_object_vars($entities[$i]);
                        $properties = array_keys($array);

                        foreach ($properties as $key => $value) {

                            array_push($custom_variable_array, $value);


                        }
                    }

                    // gets the  names of the keys (cactegories) and saves each inside an array which can then be used to form variable names with them 
                    $custom_variable_array = array_unique($custom_variable_array);

                    $custom_storage_array = array();


                    // Retrieve dataset code to use in get
                    $query = "SELECT CODE FROM DATASET WHERE ID = {$datasetId}";
                    $result = mysqli_query($con, $query);
                    $row = mysqli_fetch_array($result);
                    $dscode = $row['CODE'];

                    $_SESSION['code'] = $dscode;
                    

                    // -----------------------------------------------------------------------------------------------------------------------------------

                    foreach ($entities as $key => $entity) {


                        // Prepare and bind
                        $stmt = $con -> prepare("INSERT INTO ENTITY (NAME, TERM_ID, DESCRIPTION, LINK, DATASET_ID) VALUES (?, ?, ?, ?, ?)");
                        $stmt -> bind_param("ssssi", $entityName, $entityTermId, $entityDescription, $entityLink, $datasetId);
                        
                        // Set parameters and execute
                        $entityName = $entity->name;
                        $entityTermId = $entity->id;
                        $entityDescription = $entity->description ?? ''; // Null Coalescing operator

                        $entityLink = $entity->url ?? '';
                        
                        $stmt -> execute() or die("Unable to create new " .$custom_variable. " annotation");

                        // Gets the last id queried so the other dependent tables can use it
                        $entityId = mysqli_insert_id($con);


                        $stmt -> close();

                        foreach ($custom_variable_array as $custom_variable) {

                            if(!array_key_exists("{$custom_variable}", $entity)){
                                continue;
                            } else {
                                
                                if(is_array($entity -> $custom_variable)) {

                                    foreach ($entity -> $custom_variable as $key => $work) {

                                        $stmt = $con -> prepare("INSERT INTO ANNOTATION (ENTITY_ID, NAME, SECTION, TERM_ID, LINK) VALUES (?, ?, ?, ?, ?)");

                                        if(is_object($work)){
                                            // Prepare and bind
                                            $stmt -> bind_param("issss", $entityId, $termName, $custom_variable, $termId, $termLink);
                                            
                                            // Set parameters and execute
                                            $termName = $work->name;

                                            $termId = $work->termid;
                                            if($termId == null) {
                                                $termId = "NULL";
                                            }
                                            // Checks if the object has an url property, if it does it saves its value in $termLink
                                            $termLink = "NULL";
                                            if(property_exists($work, 'url')){
                                                $termLink = $work ->url;
                                            }
                                            // ...then it checks if the url value is null, if it is true then $termLink value will be "NULL"
                                            if($termLink == null) {
                                                $termLink = "NULL";
                                            }
                                            $stmt -> execute() or die("Unable to create new " .$custom_variable. " annotation");

                                            $stmt -> close();

                                        } else {
                                            // Prepare and bind
                                            $stmt -> bind_param("issss", $entityId, $termName, $custom_variable, $termId, $termLink);
                                            
                                            // Set parameters and execute
                                            $termName = $work;
                                            $termId = "NULL";
                                            $termLink = "NULL";
                                            $stmt -> execute() or die("Unable to create new " .$custom_variable. " annotation");

                                            $stmt -> close();
                                        }

                                    }
                                } 

                            }

                        }

                    }

                    fclose($fp);

                    
                } else{
                    $error= "Something went wrong. Please try again later.";
                }

                //Check what was the type of pairing from the radio button and update the dataset table row with it
                $sql = $con->prepare("UPDATE DATASET SET PAIR_SELECTION=? WHERE ID=?");
                $sql->bind_param('si', $param_pair_selection, $_SESSION['dataset']);
                $sql->execute();
                //check if the execute() succeeded
                if ($sql === false) {
                    trigger_error($stmt->error, E_USER_ERROR);
                }
                $sql->close();

                //every time there is an upload and this option is chosen it inserts random pairs
                $sql_select = "SELECT E1.ID AS ent1, E2.ID AS ent2, E1.DATASET_ID 
                    FROM ENTITY AS E1 JOIN ENTITY AS E2 
                    WHERE E1.DATASET_ID = ? AND E2.DATASET_ID = ?
                    AND E1.ID < E2.ID
                    ORDER BY RAND()";

                $stmt = $con->prepare($sql_select);
                $stmt->bind_param("ii", $_SESSION['dataset'], $_SESSION['dataset']);
                $stmt->execute();

                $pairs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); // fetch an array of rows
                $stmt ->close();

                foreach ($pairs as $key => $pair) {
                    $sql_insert = "INSERT INTO PAIR (ID_ENT1, ID_ENT2, ID_DS) VALUES (?, ?, ?)";
                    $stmt = $con->prepare($sql_insert);
                    $stmt-> bind_param("iii", $pair['ent1'], $pair['ent2'], $_SESSION['dataset']);
                    $stmt->execute();
                    $stmt->close();
                }
            }

        }

    } else {
        $error = "Please insert a file before submitting!";
    } 

}

unset($_FILES['fileToUpload']);

if(isset($_REQUEST['delete'])) {

    $mysqli -> query("DELETE FROM DATASET WHERE CODE = '{$_REQUEST['delete']}'");
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome</title>
    <!-- <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css"> -->
    <!-- jquery script -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

    <style type="text/css">
        body{ font: 14px sans-serif; 
            text-align: center; 
            background: linear-gradient(90deg, rgb(131, 215, 236) -50%, rgba(238, 236, 236, 0.103) 40%, rgb(131, 215, 236) 220%);}
        
            .title{ 
            background-color: #251488; 
            color: #ffffff; 
            padding: 15px; 
            box-shadow: 0px 2px 10px #251488;
        }

        table {
            margin-left:auto;
            margin-right:auto;
            border-collapse: collapse;
            width: 70%;
            border: 1px solid black;
        }

        th {
            height: 60px;
            text-align:center;
            padding: 10px;
            background-color: #251488;
            color: #ffffff;
        }

        td{
            height: 30px;
            vertical-align: bottom;
            text-align:left;
            padding: 10px;
        }

        th, td {
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }


        .grid-container{
            display:grid;
            grid-template-columns: 20% 80%;
            height: 100vh; /* make div the height of the browser window!*/

        }

        #upload_message {
            color:red;
            float:right
        }

        td .delete-ds:hover {
            background:red;
            cursor:pointer;
        }

        /*------ For the file submit form section----------------------------------------------------- */
        .tooltip {
        display:flex;
        position:relative;
        font-size: 18px;
        margin-bottom: 10px;
        }

        .tooltip .tooltipText {
        visibility: hidden;
        width: 300px;
        height: 150px;
        background-color: white;
        border: 4px solid darkcyan;
        color: black;
        text-align: justify;
        border-radius: 5px;
        padding: 5px;
        font-size: 14px;

        /* position of the tooltip */
        position: absolute;
        z-index: 1;
        margin-top: -85px;
        left: 105%;
        }

        .tooltip:hover {
            color: darkblue;
        }

        .tooltip:hover .tooltipText {
            visibility:visible;
        }

        .tooltip:hover .tooltipText::after {
            content: "";
            position:absolute;
            right: 100%;
            top: 50%;
            margin-top:-8px;
            border-width:8px;
            border-style: solid;
            border-color: transparent darkcyan transparent transparent;



        }
        /* ------------------------------------------------------------------------------------------ */

    </style>
</head>
<body>
<img src="1-512.png" alt="venn diagram" style="width:100px; height:80px; position:absolute; left:38px; z-index:1"><p style="position:absolute; top: 68px; left:68px; z-index:1"><b>MVHT</b></p>
    <div class="title">
        <h1>Manual Validation Helper Tool</h1>
    </div>
    <div class="grid-container">
    <div class="grid-item" style ="border-right: 2px solid #251488; box-shadow: 1px 0px 10px #251488; background-image: linear-gradient(to bottom, rgba(0,0,0,0), rgba(0,0,200,0.5));">

        <h1>Hi, <b><?php echo htmlspecialchars($_SESSION["username"]); ?></b>. Welcome!</h1>

        <form id="file-form" action = "" method = "post" enctype="multipart/form-data">
            <div class="radioContainer" style="float:left; padding:5px">
                <div class="tooltip">
                    <input type="radio" id="random" name="pairing" value="random" checked>
                    <label for="random">Totally Random</label><br>
                    <span class="tooltipText">Two entities will be selected randomly from the dataset, the only condition is 
                        that they must be different from each other.</span>
                </div>
                <div class="tooltip">
                    <input type="radio" id="abrangent" name="pairing" value="abrangent" checked>
                    <label for="abrangent">Fixed order</label><br>
                    <span class="tooltipText">All possible pairs will be created and then displayed to the users with their order randomised.
                        This method focuses on having distinct numbers of pairs with similarity values, so only pairs without any similarity values 
                        are shown to users. If all pairs have have already been compared then it resets and repeats the process until all
                        of them have been compared again.</span>
                </div>
                <div class="tooltip">
                    <input type="radio" id="redundant" name="pairing" value="redundant">
                    <label for="redundant">Fixed order per user</label><br>
                    <span class="tooltipText">All possible pairs will be created and displayed to the user with their order randomised. 
                        Every user will be given the same entities being compared in a certain order This method focuses on having 
                        multiple comparisons between the same entities.</span>
                </div>
            </div>
            <label for="fileToUpload" style="float:left; margin-left:3px; margin-top:15px; clear:left;">Select a file:</label>
            <input type="file" name = "fileToUpload" id = "fileToUpload" style="float:left; 100px; font-size:18px">
            <p id="upload_message"><b><?php echo htmlspecialchars($error); ?></b></p>
            <input type="submit" value="Upload file" name="submit" style="float:left; margin-top: 10px; width:112px; font-size:18px">
        </form>

        <div id="err"></div>

        <p style="font-size:18px; margin-top: 230px; display:flex; clear:left;">
            <a href="logout.php" class="btn btn-danger">Sign Out of Your Account</a>
        </p>
    </div>
    
    <div>
        <table style="margin-top: 5%">
            <tr>
                <th>Dataset</th>
                <th>Name</th>
                <th>Entities</th>
                <th>Annotations</th>
                <th>Responses</th>
                <th>URL</th>
                <th>Results</th>
                <th>Delete</th>
            </tr>
            <?php 
            $sql = "SELECT DATASET.ID, DATASET.CODE, DATASET.NAME, COUNT(ENTITY.ID) AS entities FROM DATASET INNER JOIN ENTITY ON DATASET.ID = ENTITY.DATASET_ID WHERE USER_ID={$_SESSION['id']} GROUP BY DATASET.ID";
            $result = mysqli_query($con, $sql);
            if(mysqli_num_rows($result)>0) {

                while($row = mysqli_fetch_assoc($result)) {
                    $code = $row['CODE'];
                    $id = $row['ID'];
                ?>
                <tr>
                    <td><?php echo $row['CODE']; ?></td>
                    <td><?php echo $row['NAME']; ?></td>
                    <td><?php echo $row['entities']; ?></td>
                    <td>
                        <?php
                        $sql = "SELECT COUNT(ANNOTATION.ID) FROM ANNOTATION INNER JOIN ENTITY ON ANNOTATION.ENTITY_ID = ENTITY.ID INNER JOIN DATASET ON ENTITY.DATASET_ID = DATASET.ID WHERE DATASET.CODE = '{$code}'";
                        $res = mysqli_query($con, $sql);
                        $row = mysqli_fetch_row($res);
                        echo $row[0];
                        ?>
                    </td>
                    <td>
                        <?php
                        $sql = "SELECT COUNT(VALUE) FROM SIMILARITY_VALUE WHERE DATASET_CODE = '{$code}'";
                        $res = mysqli_query($con, $sql);
                        $row = mysqli_fetch_row($res);
                        echo $row[0];
                        ?>
                    </td>
                    <td>
                    <a href = "/insert_users.php?code=<?php echo $code;?>">mvht.lasige.di.fc.ul.pt/insert_users.php?code=<?php echo $code;?></a>
                    <!-- /insert_users.php?code= -->
                    <!-- http://localhost/projects/kegg%20files/insert_users.php?code= -->
                    </td>
                    <td><a href= "/download_index.php?code=<?php echo $code;?>" >Click</a></td>
                    <td>
                    <span class="delete" id="delete-ds_<?= $id ?>"><img src="minus-circle-512.png" alt="minus-sign" style="width:30px; height:30px;"></span>
                    </td>
                </tr>
                <?php
                }
            } else {
                ?>
                <tr>
                </tr>
                <?php
            }

            // Close connection
            mysqli_close($con);
            ?>
        </table>
    </div>

</div>

<script type="text/javascript">
    $(document).ready(function() {
        $(".delete").click(function() {
            var el = this;
            var id = this.id;
            var splitid = id.split('_');

            // Delete code
            var deleteid = splitid[1];

            // AJAX request
            $.ajax({
                url: 'remove-ds.php',
                type: 'POST',
                data: {id:deleteid},
                success: function(response) {
                    if(response == 1) {
                        // Remove row from HTML table
                        $(el).closest('tr').css('background', 'gray');
                        $(el).closest('tr').fadeOut(500, function(){
                            $(this).remove();
                        });

                    } else {
                        alert("could not delete dataset with id: " . deleteid);
                    }
                }
            });
            
        });
    });
</script>
</body>
</html>
