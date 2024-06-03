<?php

// Set the allowed origin to match the request origin
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Assuming you have a database connection established already
// Replace these variables with your actual database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "backend";

// Create connection
$connection = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Read the raw POST data
    $data = file_get_contents("php://input");

    // Decode the JSON data
    $json_data = json_decode($data);

    // Check if JSON decoding was successful
    if ($json_data === null) {
        die("Error decoding JSON data");
    }

    // Extract studentNo and password from JSON data
    $studentNo = $json_data->studentNo;
    $password = $json_data->password;

    // Proceed with authentication
    if (!empty($studentNo) && !empty($password)) {
        // Sanitize inputs to prevent SQL injection
        $studentNo = mysqli_real_escape_string($connection, $studentNo);
        $password = mysqli_real_escape_string($connection, $password);

        // SQL query to check if the student number and password exist in the database
        $query = "SELECT * FROM personalinformation WHERE authid = '$studentNo' AND psword = '$password'";

        // Perform the query
        $result = mysqli_query($connection, $query);
        // Check if there is a row returned
        if(mysqli_num_rows($result) > 0) {
            // Student number and password exist in the database
            // Fetch all data from personalinformation, skills, and timeregistration tables
            $userDataQuery = "
            SELECT p.*, s.*, t.*
            FROM personalinformation p
            LEFT JOIN skills s ON p.authid = s.memberId
            LEFT JOIN timeinformation t ON p.authid = t.memberId
            ";
            $userDataResult = mysqli_query($connection, $userDataQuery);

            // Fetch all rows of data
            $userData = mysqli_fetch_all($userDataResult, MYSQLI_ASSOC);

            // Close the database connection
            mysqli_close($connection);

            // Convert the data to JSON format
            $userDataJSON = json_encode($userData);

            // Send JSON response
            header('Content-Type: application/json');
            echo $userDataJSON;
        } else {
            // Student number and/or password do not exist in the database
            // Return authentication failed message
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode(array("message" => "Authentication failed. Invalid studentNo or password."));
        }
    } else {
        // Invalid studentNo or password
        // Return invalid input message
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(array("message" => "Invalid studentNo or password"));
    }
} else {
    // Handle invalid request method
    // Return invalid request method message
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(array("message" => "Invalid request method"));
}
