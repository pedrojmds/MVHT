<?php
    
    session_start();

    include("config.php");

?>

<html>
    <head>
        <link rel="stylesheet" href="style.css">
    </head>
        <div class="title">
            <h1>Manual Validation Helper Tool</h1>
        </div>
    <form action="" method="post">
        <div class="field-container">
            <label>Name: </label><input type="text" name="user">
            <br>
            <label>Field of expertise: </label><input type="text" name="field"> 
            <br>
            <input type="submit" name="submit">
        </div>
    </form>

</html>

<?php

    if(!isset($_GET['code'])) {

        $errorMessage = "Invalid url, dataset is missing!";
        echo "<script type='text/javascript'> alert('$errorMessage'); </script>";

    } else {
        // Check if code is in database (in case user changes code in url bar)
        $sql = "SELECT CODE FROM DATASET WHERE CODE= ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("s", $_GET['code']);
        $stmt->execute();
        $result = $stmt->get_result();
        $codeMatch = $result->num_rows;
        if($codeMatch != 1){
            echo "<h2 style='text-align:center; color:red'>Dataset does not exist</h2>";
            exit(); //end script
        } else {

            if (isset($_POST['submit'])) {
            
                $user = $_POST['user'];
                $field = $_POST['field'];
        
                if (empty($user) || empty($field)){
                    $errorMessage = "Please fill in all the fields!";
                    echo "<h2 style='text-align:center; color:red;'>".$errorMessage."</h2>";
                }
                else {

                    if(!preg_match("/^\pL+(?>[- ']\pL+)*$/u", $user) || !preg_match("/^\pL+(?>[- ']\pL+)*$/u", $field)) {
                        $errorMessage = "Invalid characters in one of the fields";

                        echo "<h2 style='text-align:center; color:red;'>".$errorMessage."</h2>";
                    } else {
                        $stmt = $con -> prepare("INSERT INTO USER (NAME, FIELD) VALUES (?, ?)");
                        $stmt -> bind_param("ss", $user_name, $user_field);

                        $user_name = $_POST['user'];
                        $user_field = $_POST['field'];
                        
                        $stmt -> execute() or die("Unable to create new user");
                        $stmt -> close();
                        
                        ?>
                        <script type ="text/javascript">
                            window.location = "retrieve_data.php?code=<?php echo $_GET['code']?>"; //Redirects to app page using javascript 
                        </script>
                        <?php
                    }
                }
            }
        
        
            if (isset($_POST['user']) and isset($_POST['field'])){
                if(!isset($_SESSION)){
                    session_start();
                }
                $_SESSION['userid'] = mysqli_insert_id($con);
        
            }
            
        }
    }
    
    mysqli_close($con);



?>

