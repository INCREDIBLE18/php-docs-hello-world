
<?php
session_start();
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'student_portal';

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Register User
if (isset($_POST['register'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $query = "INSERT INTO students (first_name, last_name, email, password) VALUES ('$first_name', '$last_name', '$email', '$password')";
    
    if ($conn->query($query) === TRUE) {
        echo "Registration successful!";
    } else {
        echo "Error: " . $conn->error;
    }
}

// Login User
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT * FROM students WHERE email = '$email'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();
        if (password_verify($password, $student['password'])) {
            $_SESSION['student_id'] = $student['id'];
            $_SESSION['first_name'] = $student['first_name'];
            header('Location: index.php');
            exit();
        } else {
            echo "Invalid credentials!";
        }
    } else {
        echo "No such user found!";
    }
}

// Enroll in Course
if (isset($_GET['enroll'])) {
    if (!isset($_SESSION['student_id'])) {
        echo "Please login to enroll!";
    } else {
        $course_id = $_GET['enroll'];
        $student_id = $_SESSION['student_id'];

        // Check if already enrolled
        $check_enrollment = "SELECT * FROM enrollments WHERE student_id = '$student_id' AND course_id = '$course_id'";
        $result = $conn->query($check_enrollment);
        
        if ($result->num_rows == 0) {
            $query = "INSERT INTO enrollments (student_id, course_id) VALUES ('$student_id', '$course_id')";
            if ($conn->query($query) === TRUE) {
                echo "Enrollment successful!";
            } else {
                echo "Error: " . $conn->error;
            }
        } else {
            echo "You are already enrolled in this course.";
        }
    }
}

// Mark Attendance
if (isset($_POST['mark_attendance'])) {
    if (!isset($_SESSION['student_id'])) {
        echo "Please login to mark attendance!";
    } else {
        $course_id = $_POST['course_id'];
        $student_id = $_SESSION['student_id'];
        $status = $_POST['status'];
        $date = $_POST['date'];

        $query = "INSERT INTO attendance (student_id, course_id, date, status) VALUES ('$student_id', '$course_id', '$date', '$status')";
        if ($conn->query($query) === TRUE) {
            echo "Attendance marked successfully!";
        } else {
            echo "Error: " . $conn->error;
        }
    }
}

// Fetch available courses
$query_courses = "SELECT * FROM courses";
$courses = $conn->query($query_courses);

// Fetch enrolled courses
if (isset($_SESSION['student_id'])) {
    $student_id = $_SESSION['student_id'];
    $query_enrollments = "SELECT courses.course_name, courses.course_code FROM enrollments 
                           JOIN courses ON enrollments.course_id = courses.id
                           WHERE enrollments.student_id = '$student_id'";
    $enrolled_courses = $conn->query($query_enrollments);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal</title>
</head>
<body>

<?php if (!isset($_SESSION['student_id'])): ?>
    <!-- Registration Form -->
    <h2>Register</h2>
    <form method="POST" action="">
        First Name: <input type="text" name="first_name" required><br>
        Last Name: <input type="text" name="last_name" required><br>
        Email: <input type="email" name="email" required><br>
        Password: <input type="password" name="password" required><br>
        <input type="submit" name="register" value="Register">
    </form>

    <!-- Login Form -->
    <h2>Login</h2>
    <form method="POST" action="">
        Email: <input type="email" name="email" required><br>
        Password: <input type="password" name="password" required><br>
        <input type="submit" name="login" value="Login">
    </form>

<?php else: ?>
    <h1>Welcome, <?php echo $_SESSION['first_name']; ?></h1>
    <a href="index.php?logout=true">Logout</a>

    <h2>Available Courses</h2>
    <ul>
        <?php while ($course = $courses->fetch_assoc()): ?>
            <li><?php echo $course['course_name']; ?> 
                <a href="index.php?enroll=<?php echo $course['id']; ?>">Enroll</a>
            </li>
        <?php endwhile; ?>
    </ul>

    <h2>Your Enrolled Courses</h2>
    <ul>
        <?php while ($enrolled = $enrolled_courses->fetch_assoc()): ?>
            <li><?php echo $enrolled['course_name']; ?></li>
        <?php endwhile; ?>
    </ul>

    <h2>Mark Attendance</h2>
    <form method="POST" action="">
        Course ID: <input type="number" name="course_id" required><br>
        Date: <input type="date" name="date" required><br>
        Status: 
        <select name="status" required>
            <option value="Present">Present</option>
            <option value="Absent">Absent</option>
        </select><br>
        <input type="submit" name="mark_attendance" value="Mark Attendance">
    </form>
<?php endif; ?>

</body>
</html>

<?php
// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit();
}
?>
