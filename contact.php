<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_username');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'your_db_name');
define('ADMIN_EMAIL', 'accglobalsolar@gmail.com');

$success = '';
$error = '';
$form_type = '';
function getDB() {
    static $db = null;
    if ($db === null) {
        try {
            $db = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER,
                DB_PASS
            );
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            return null;
        }
    }
    return $db;
}
function initTables() {
    $db = getDB();
    if (!$db) return;
    
    $sql1 = "CREATE TABLE IF NOT EXISTS contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        subject VARCHAR(200),
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('new', 'read', 'replied') DEFAULT 'new'
    )";
    
    $sql2 = "CREATE TABLE IF NOT EXISTS subscribers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) NOT NULL UNIQUE,
        subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('active', 'unsubscribed') DEFAULT 'active'
    )";
    
    $db->exec($sql1);
    $db->exec($sql2);
}
initTables();
function sendMail($to, $subject, $body, $replyTo = '') {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: A.C C Global Solar <noreply@accglobalsolar.com>\r\n";
    if ($replyTo) {
        $headers .= "Reply-To: " . $replyTo . "\r\n";
    }
    return mail($to, $subject, $body, $headers);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' 
    && isset($_POST['form_type']) 
    && $_POST['form_type'] === 'contact') {
    
    $form_type = 'contact';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($message)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($message) < 10) {
        $error = 'Message must be at least 10 characters.';
    } else {
        $db = getDB();
        if ($db) {
            $stmt = $db->prepare(
                "INSERT INTO contacts 
                (name, email, phone, subject, message) 
                VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([$name, $email, $phone, $subject, $message]);
        }
        
        $adminBody = "<h2>New Contact</h2>";
        $adminBody .= "<p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>";
        $adminBody .= "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
        $adminBody .= "<p><strong>Phone:</strong> " . htmlspecialchars($phone) . "</p>";
        $adminBody .= "<p><strong>Subject:</strong> " . htmlspecialchars($subject) . "</p>";
        $adminBody .= "<p><strong>Message:</strong></p>";
        $adminBody .= "<p>" . nl2br(htmlspecialchars($message)) . "</p>";
        
        sendMail(
            ADMIN_EMAIL,
            "New Contact: " . ($subject ?: 'No Subject'),
            $adminBody,
            $email
        );
        $customerBody = "<h2>Thank you for contacting us!</h2>";
        $customerBody .= "<p>Hi " . htmlspecialchars($name) . ",</p>";
        $customerBody .= "<p>We received your message and will reply soon.</p>";
        $customerBody .= "<p>For faster response, ";
        $customerBody .= "<a href='https://wa.me/2349065604615'>chat on WhatsApp</a>.</p>";
        
        sendMail($email, "We received your message", $customerBody);
        
        $success = 'Thank you! Your message has been sent.';
        $_POST = [];
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' 
    && isset($_POST['form_type']) 
    && $_POST['form_type'] === 'subscribe') {
    
    $form_type = 'subscribe';
    $email = trim($_POST['subscribe_email'] ?? '');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $db = getDB();
        if ($db) {
            try {
                $stmt = $db->prepare(
                    "INSERT INTO subscribers (email) VALUES (?)"
                );
                $stmt->execute([$email]);
                
                $welcome = "<h2>Welcome!</h2>";
                $welcome .= "<p>Thank you for subscribing to ";
                $welcome .= "A.C C Global Solar Energy.</p>";
                
                sendMail($email, "Welcome to our Newsletter!", $welcome);
                
                $success = 'You have successfully subscribed!';
            } catch (PDOException $e) {
                $error = 'This email is already subscribed!';
            }
        } else {
            $success = 'Thank you for subscribing!';
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <title>Contact Us | A.C C Global Solar Energy - Solar Power Solutions Nigeria</title>
        <meta content="width=device-width, initial-scale=1.0" name="viewport">
        <meta content="contact A.C C Global Solar Energy, solar energy Lagos, Alaba International Market, solar panels Nigeria" name="keywords">
        <meta content="Contact A.C C Global Solar Energy at Shop F1485B, Alaba International Market, Ojo, Lagos. Call 0906 560 4615 for solar panels, batteries, inverters, and nationwide delivery." name="description">

        <!-- Google Web Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Jost:wght@500;600&family=Roboto&display=swap" rel="stylesheet"> 

        <!-- Icon Font Stylesheet -->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

        <!-- Customized Bootstrap Stylesheet -->
        <link href="bootstrap.min.css" rel="stylesheet">

        <!-- Template Stylesheet -->
        <link href="style.css" rel="stylesheet">
    </head>

    <body>

        <!-- Spinner Start -->
        <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <!-- Spinner End -->

        <!-- Topbar Start -->
        <div class="container-fluid bg-primary px-5 d-none d-lg-block">
            <div class="row gx-0">
                <div class="col-lg-8 text-center text-lg-start mb-2 mb-lg-0">
                    <div class="d-inline-flex align-items-center" style="height: 45px;">
                        <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle me-2" href="https://facebook.com/accglobalsolar" target="_blank"><i class="fab fa-facebook-f fw-normal"></i></a>
                        <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle me-2" href="https://instagram.com/accglobalsolar" target="_blank"><i class="fab fa-instagram fw-normal"></i></a>
                        <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle me-2" href="https://wa.me/2349065604615" target="_blank"><i class="fab fa-whatsapp fw-normal"></i></a>
                        <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle me-2" href="#"><i class="fab fa-twitter fw-normal"></i></a>
                        <a class="btn btn-sm btn-outline-light btn-sm-square rounded-circle" href="#"><i class="fab fa-youtube fw-normal"></i></a>
                    </div>
                </div>
                <div class="col-lg-4 text-center text-lg-end">
                    <div class="d-inline-flex align-items-center" style="height: 45px;">
                        <a href="tel:+2349065604615"><small class="me-3 text-light"><i class="fa fa-phone-alt me-2"></i>0906 560 4615</small></a>
                        <a href="#"><small class="me-3 text-light"><i class="fa fa-truck me-2"></i>Nationwide Delivery</small></a>
                        <a href="https://wa.me/2349065604615" target="_blank"><small class="text-light"><i class="fab fa-whatsapp me-2"></i>Chat on WhatsApp</small></a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Topbar End -->

        <!-- Navbar & Hero Start -->
        <div class="container-fluid position-relative p-0">
            <nav class="navbar navbar-expand-lg navbar-light px-4 px-lg-5 py-3 py-lg-0">
                <a href="index.html" class="navbar-brand p-0">
                    <h1 class="m-0"><i class="fa fa-sun me-3"></i>A.C C Global Solar Energy</h1>
                    <!-- <img src="img/logo.png" alt="Logo"> -->
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                    <span class="fa fa-bars"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarCollapse">
                    <div class="navbar-nav ms-auto py-0">
                        <a href="index.html" class="nav-item nav-link">Home</a>
                        <a href="about.html" class="nav-item nav-link">About</a>
                        
                        <!-- Services Dropdown -->
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Services</a>
                            <div class="dropdown-menu rounded m-0">
                                <a href="services.html" class="dropdown-item">Solar Installation</a>
                                <a href="services.html" class="dropdown-item">Maintenance & Repair</a>
                                <a href="services.html" class="dropdown-item">Energy Consultation</a>
                                <a href="services.html" class="dropdown-item">Nationwide Delivery</a>
                            </div>
                        </div>
                        
                        <!-- Products Dropdown -->
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Products</a>
                            <div class="dropdown-menu rounded m-0">
                                <a href="solar-panels.html" class="dropdown-item">Solar Panels</a>
                                <a href="solar-batteries.html" class="dropdown-item">Solar Batteries</a>
                                <a href="solar-inverters.html" class="dropdown-item">Solar Inverters</a>
                                <a href="charge-controllers.html" class="dropdown-item">Charge Controllers</a>
                                <a href="solar-freezers.html" class="dropdown-item">Solar Freezers</a>
                                <a href="solar-generators.html" class="dropdown-item">Solar Generators</a>
                                <div class="dropdown-divider"></div>
                                <a href="other-products.html" class="dropdown-item">Other Products</a>
                            </div>
                        </div>
                        
                        <a href="contact.html" class="nav-item nav-link active">Contact</a>
                    </div>
                    <a href="https://wa.me/2349065604615?text=Hello%20A.C%20C%20Global%20Solar%20Energy,%20I%20want%20to%20get%20a%20quote." class="btn btn-primary rounded-pill py-2 px-4 ms-lg-4" target="_blank"><i class="fab fa-whatsapp me-2"></i>Get a Quote</a>
                </div>
            </nav>
        </div>
        <!-- Navbar & Hero End -->

        <!-- Header Start -->
        <div class="container-fluid bg-breadcrumb">
            <div class="container text-center py-5" style="max-width: 900px;">
                <h3 class="text-white display-3 mb-4">Contact Us</h3>
                <ol class="breadcrumb justify-content-center mb-0">
                    <li class="breadcrumb-item"><a href="index.html">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Pages</a></li>
                    <li class="breadcrumb-item active text-white">Contact</li>
                </ol>    
            </div>
        </div>
        <!-- Header End -->

        <!-- Contact Start -->
        <div class="container-fluid contact bg-light py-5">
            <div class="container py-5">
                <div class="mx-auto text-center mb-5" style="max-width: 900px;">
                    <h5 class="section-title px-3">Contact Us</h5>
                    <h1 class="mb-0">Get In Touch With Us</h1>
                </div>
                <div class="row g-5 align-items-center">
                    <div class="col-lg-4">
                        <div class="bg-white rounded p-4">
                            <div class="text-center mb-4">
                                <i class="fa fa-map-marker-alt fa-3x text-primary"></i>
                                <h4 class="text-primary">Address</h4>
                                <p class="mb-0">Shop F1485B, Alaba International Market,<br> Ojo, Lagos State, Nigeria</p>
                            </div>
                            <div class="text-center mb-4">
                                <i class="fa fa-phone-alt fa-3x text-primary mb-3"></i>
                                <h4 class="text-primary">Mobile</h4>
                                <p class="mb-0"><a href="tel:+2349065604615">0906 560 4615</a></p>
                                <p class="mb-0"><a href="tel:+23481050384">0810 503 8445</a></p>
                            </div>
                            <div class="text-center mb-4">
                                <i class="fab fa-whatsapp fa-3x text-primary mb-3"></i>
                                <h4 class="text-primary">WhatsApp</h4>
                                <p class="mb-0"><a href="https://wa.me/2349065604615" target="_blank">0906 560 4615</a></p>
                            </div>
                            <div class="text-center">
                                <i class="fa fa-envelope-open fa-3x text-primary mb-3"></i>
                                <h4 class="text-primary">Email</h4>
                                <p class="mb-0"><a href="mailto:accglobalsolar@gmail.com">accglobalsolar@gmail.com</a></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8">
                        <h2 class="mb-3">Send us a message</h2>
                        <h4 class="lh-base mb-4">Have a question about our solar products or services? Fill out the form below and we'll get back to you as soon as possible. For faster response, <a href="https://wa.me/2349065604615" target="_blank">chat with us on WhatsApp</a>.</h4>
                        <form action="contact.php" method="POST">
    <input type="hidden" name="form_type" value="contact">
    <!-- rest of your form stays the same -->

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control border-0" id="name" placeholder="Your Name" required>
                                        <label for="name">Your Name</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="email" class="form-control border-0" id="email" placeholder="Your Email" required>
                                        <label for="email">Your Email</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <input type="text" class="form-control border-0" id="subject" placeholder="Subject" required>
                                        <label for="subject">Subject</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-floating">
                                        <textarea class="form-control border-0" placeholder="Leave a message here" id="message" style="height: 160px" required></textarea>
                                        <label for="message">Message</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-primary w-100 py-3" type="submit">Send Message</button>
                                </div>
                            </div>
                        </form>
                        <div class="text-center mt-4">
                            <p class="mb-2">Or reach us directly:</p>
                            <a href="https://wa.me/2349065604615?text=Hello%20A.C%20C%20Global%20Solar%20Energy,%20I%20have%20an%20enquiry." class="btn btn-success rounded-pill py-2 px-4 me-2" target="_blank">
                                <i class="fab fa-whatsapp me-2"></i>WhatsApp Us
                            </a>
                            <a href="tel:+2349065604615" class="btn btn-primary rounded-pill py-2 px-4">
                                <i class="fa fa-phone-alt me-2"></i>Call Now
                            </a>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="rounded">
                            <iframe class="rounded w-100" 
                            style="height: 450px;" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3963.8!2d3.123456!3d6.456789!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2sAlaba%20International%20Market%2C%20Ojo%2C%20Lagos!5e0!3m2!1sen!2sng!4v1694259649153!5m2!1sen!2sng" 
                            loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Contact End -->

        <!-- Subscribe Start -->
        <div class="container-fluid subscribe py-5">
            <div class="container text-center py-5">
                <div class="mx-auto text-center" style="max-width: 900px;">
                    <h5 class="subscribe-title px-3">Stay Updated</h5>
                    <h1 class="text-white mb-4">Get The Latest Deals</h1>
                    <p class="text-white mb-5">Subscribe to receive updates on new solar products, price drops, restocks, and exclusive offers. Be the first to know when we get new arrivals at A.C C Global Solar Energy.
                    </p>
<form action="contact.php" method="POST">
    <input type="hidden" name="form_type" value="subscribe">
    <div class="position-relative mx-auto">
        <input class="form-control border-primary rounded-pill w-100 py-3 ps-4 pe-5" 
               type="email" 
               name="subscribe_email" 
               placeholder="Enter your email address" 
               required>
        <button type="submit" 
                class="btn btn-primary rounded-pill position-absolute top-0 end-0 py-2 px-4 mt-2 me-2">
            Subscribe
        </button>
    </div>
</form>
</div>
        </div>
        <!-- Subscribe End -->
<!-- Footer Start -->
<div class="container-fluid footer py-5">
    <div class="container py-5">
        <div class="row g-5">
            <!-- Column 1: Get In Touch -->
            <div class="col-md-6 col-lg-6 col-xl-3">
                <div class="footer-item d-flex flex-column">
                    <h4 class="mb-4 text-white">Get In Touch</h4>
                    <a href="https://maps.google.com/?q=Alaba+International+Market+Ojo+Lagos" target="_blank"><i class="fas fa-home me-2"></i> Shop F1485B, Alaba International Market, Ojo, Lagos</a>
                    <a href="mailto:accglobalsolar@gmail.com"><i class="fas fa-envelope me-2"></i> accglobalsolar@gmail.com</a>
                    <a href="tel:+2349065604615"><i class="fas fa-phone me-2"></i> 0906 560 4615</a>
                    <a href="tel:+23481050384" class="mb-3"><i class="fab fa-whatsapp me-2"></i> 0810 503 8445</a>
                    <div class="d-flex align-items-center">
                        <i class="fas fa-share fa-2x text-white me-2"></i>
                        <a class="btn-square btn btn-primary rounded-circle mx-1" href="https://facebook.com/accglobalsolar" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <a class="btn-square btn btn-primary rounded-circle mx-1" href="https://instagram.com/accglobalsolar" target="_blank"><i class="fab fa-instagram"></i></a>
                        <a class="btn-square btn btn-primary rounded-circle mx-1" href="https://wa.me/2349065604615" target="_blank"><i class="fab fa-whatsapp"></i></a>
                        <a class="btn-square btn btn-primary rounded-circle mx-1" href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>
            
            <!-- Column 2: Company -->
            <div class="col-md-6 col-lg-6 col-xl-3">
                <div class="footer-item d-flex flex-column">
                    <h4 class="mb-4 text-white">Company</h4>
                    <a href="about.html"><i class="fas fa-angle-right me-2"></i> About Us</a>
                    <a href="services.html"><i class="fas fa-angle-right me-2"></i> Our Services</a>
                    <a href="solar-panels.html"><i class="fas fa-angle-right me-2"></i> Solar Products</a>
                    <a href="other-products.html"><i class="fas fa-angle-right me-2"></i> Other Products</a>
                </div>
            </div>
            
            
            <!-- Column 4: Business Hours (Replaced Language/Currency) -->
            <div class="col-md-6 col-lg-6 col-xl-3">
                <div class="footer-item">
                    <h4 class="text-white mb-4">Business Hours</h4>
                    <div class="bg-dark rounded p-3 mb-4">
                        <div class="d-flex justify-content-between text-white mb-2">
                            <span>Monday – Saturday</span>
                            <span class="text-primary">8:00 AM – 6:00 PM</span>
                        </div>
                        <div class="d-flex justify-content-between text-white mb-2">
                            <span>Sunday</span>
                            <span class="text-danger">Closed</span>
                        </div>
                        <div class="d-flex justify-content-between text-white">
                            <span>Public Holidays</span>
                            <span class="text-warning">Call First</span>
                        </div>
                    </div>
                    
                    <h4 class="text-white mb-3 mt-4">Quick Quote</h4>
                    <a href="https://wa.me/2349065604615?text=Hello%20A.C%20C%20Global%20Solar%20Energy,%20I%20want%20to%20get%20a%20quick%20quote." class="btn btn-success rounded-pill w-100 py-2" target="_blank">
                        <i class="fab fa-whatsapp me-2"></i>Chat on WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Footer End -->

<!-- Copyright Start -->
<div class="container-fluid copyright text-body py-4">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-md-6 text-center text-md-end mb-md-0">
                <i class="fas fa-copyright me-2"></i><a class="text-white" href="index.html">A.C C Global Solar Energy</a>, All rights reserved.
            </div>
        </div>
    </div>
</div>
<!-- Copyright End -->

<!-- Back to Top -->
<a href="#" class="btn btn-primary btn-primary-outline-0 btn-md-square back-to-top"><i class="fa fa-arrow-up"></i></a>

        
        <!-- JavaScript Libraries -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="easing.min.js"></script>
        <script src="waypoints.min.js"></script>
        

        <!-- Template Javascript -->
        <script src="main.js"></script>
    </body>

</html>
