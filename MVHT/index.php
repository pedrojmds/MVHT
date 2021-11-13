<?php
// Initialize the session
session_start();
 
// Check if the user is already logged in, if yes then redirect him to welcome page
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: welcome.php");
    exit;
}
 
// Include config file
require_once "config.php";
 
// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = "";
 
// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
 
    // Check if username is empty
    if(empty(trim($_POST["username"]))){
        $username_err = "Please enter username.";
    } else{
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if(empty(trim($_POST["password"]))){
        $password_err = "Please enter your password.";
    } else{
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if(empty($username_err) && empty($password_err)){
        // Prepare a select statement
        $sql = "SELECT ID, USERNAME, PASSWORD FROM USER_2nd WHERE USERNAME = ?";
        
        if($stmt = mysqli_prepare($con, $sql)){
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            
            // Set parameters
            $param_username = $username;
            
            // Attempt to execute the prepared statement
            if(mysqli_stmt_execute($stmt)){
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if username exists, if yes then verify password
                if(mysqli_stmt_num_rows($stmt) == 1){                    
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password);
                    if(mysqli_stmt_fetch($stmt)){
                        if(password_verify($password, $hashed_password)){
                            // Password is correct, so start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;                            
                            
                            // Redirect user to welcome page
                            header("location: welcome.php");
                        } else{
                            // Display an error message if password is not valid
                            $password_err = "The password you entered was not valid.";
                        }
                    }
                } else{
                    // Display an error message if username doesn't exist
                    $username_err = "No account found with that username.";
                }
            } else{
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Close connection
    mysqli_close($con);
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.css">
    <link rel="stylesheet" href="./prism.css">
    <style type="text/css">
        body{ 
            font: 14px sans-serif; 
            background: linear-gradient(90deg, rgb(131, 215, 236) -50%, rgba(238, 236, 236, 0.103) 40%, rgb(131, 215, 236) 220%);
        }
        .wrapper-login{ width: 450px; padding: 20px;}
        .title{ background-color: #251488; color: #ffffff; padding: 15px;}
        .tutorial p{
            font-size:20px;
            text-align: justify;
        }

        .table-text-form{ 
            display: table;
        }

        .tutorial{
            padding: 20px;
            vertical-align: middle;
            display:table-cell; /* necessary in order to center the paragraphs*/
        }

        table {
            border-collapse:collapse;
            width:100%;
        }
        th,td {
            border: 1px solid #dddddd;
            text-align:left;
            padding: 10px;
        }

        th {
            color:blue;
            font-size:large;
        }

        #navlink {
            float:left; 
            height:99px; 
            border-left: 2px inset black; 
            border-right:2px outset gray;
            box-shadow: 0px 0px 10px 2px black inset;
        }

        #navlink:hover {
            background-color: darkcyan;
            color: whitesmoke;
        }

    </style>
</head>
<body>
    
    <div class="topnav" style="height:100px; background-color: #251488">
    <span><img src="1-512.png" alt="venn diagram" style="width:100px; height:80px; position:absolute; left:38px;"><p style="position:absolute; top: 68px; left:68px;"><b style="color:black">MVHT</b></p></span>
    <div class="title" style="float:left">
        <h1 style="margin-left: 140px">Manual Validation Helper Tool</h1>
    </div>
    <a style="text-decoration:none;" href="format.html" ><div id="navlink" class="title"><p style="font-size: 18px; padding:30px">Format Guide</p></div></a>
    </div>
    <div class ="table-text-form" style="height:500px; display: table;">
    <div class="tutorial">
        <p>This tool was made with the objective of helping semantic similarity developers to validate their measures by inserting their datasets and
        giving a link to other people so they can make comparisons between entities in said dataset. This way a developer can save and later use the answers provided
        as a means for manual validation of similarity measures.
        </p>
        <h3><b><u>How to use:</u></b></h3>
        <p>First please log in into your account on the right of this page or sign up in case you do not have one. Then you will be able to insert 
        your datasets into the tool which will store them and generate a link you can given to other people. Each dataset
        will have a unique code. From your user page you will also be given the possibility of seeing the compared results from
        a dataset and download them in a CSV file. When a dataset is no longer needed it can be deleted by pressing the "minus"
        sign.</p>
        <p>In order to correctly display the information on the page <b>uploaded datasets should be given using a specific format in a json file</b> Please click on the "Format Guide" tab at the top of the page for instruction on how to build the dataset.
        

    </div>

    <div class="wrapper-login">
        <h2>Login</h2>
        <p>Please fill in your credentials to login.</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group <?php echo (!empty($username_err)) ? 'has-error' : ''; ?>">
                <label>Username</label>
                <input type="text" name="username" class="form-control" value="<?php echo $username; ?>">
                <span class="help-block"><?php echo $username_err; ?></span>
            </div>    
            <div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
                <label>Password</label>
                <input type="password" name="password" class="form-control">
                <span class="help-block"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Login">
            </div>
            <p>Don't have an account? <a href="register.php">Sign up now</a>.</p>
        </form>
    </div>   
    </div>

    <script src="./prism.js"></script> 
</body>
</html>
