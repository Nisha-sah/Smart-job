<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Portal | Find Your Dream Job</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

    <header>
        <nav class="navbar">
            <div class="logo">
                <i class="fas fa-briefcase"></i> Job Portal
            </div>
            <ul class="nav-links">
                <li><a href="#">Home</a></li>
                <li><a href="#">Jobs</a></li>
                <li><a href="#">Contact Us</a></li>
            </ul>
            <div class="auth-buttons">
                <a href="../login/login.php" class="login-btn">Login</a>
                <a href="../login/register.php" class="register-btn">Register</a>
            </div>
        </nav>

        <div class="hero">
            <h1>Find Your Dream Job Today!</h1>
            <p>Connecting Talent with Opportunity: Your Gateway to Career Success</p>
            
            <div class="search-container">
                <input type="text" placeholder="Job Title or Company">
                <select>
                    <option>Select Location</option>
                </select>
                <select>
                    <option>Select Category</option>
                </select>
                <button class="search-btn"><i class="fas fa-search"></i> Search Job</button>
            </div>

            <div class="stats">
                <div class="stat-item"><i class="fas fa-briefcase"></i> 25,850 Jobs</div>
                <div class="stat-item"><i class="fas fa-users"></i> 10,250 Candidates</div>
                <div class="stat-item"><i class="fas fa-building"></i> 18,400 Companies</div>
            </div>
        </div>
    </header>

    <main class="container">
        <div class="section-header">
            <h2>Recent Jobs Available</h2>
            <a href="#" class="view-all">View all</a>
        </div>

        <div class="job-list">
            <div class="job-card">
                <div class="job-info">
                    <div class="company-logo red"></div>
                    <div>
                        <h3>Forward Security Director</h3>
                        <p class="company-name">Gauch, Schuppe and Schukit Co</p>
                    </div>
                </div>
                <div class="job-meta">
                    <span><i class="fas fa-hotel"></i> Hotels & Tourism</span>
                    <span><i class="far fa-clock"></i> Full time</span>
                    <span><i class="fas fa-money-bill-wave"></i> $40k - $45k</span>
                    <span><i class="fas fa-map-marker-alt"></i> New York, USA</span>
                </div>
                <button class="details-btn">Job Details</button>
                <i class="far fa-bookmark save-icon"></i>
            </div>

            <div class="job-card">
                <div class="job-info">
                    <div class="company-logo orange"></div>
                    <div>
                        <h3>Regional Creative Facilitator</h3>
                        <p class="company-name">Walsh - Becker Co</p>
                    </div>
                </div>
                <div class="job-meta">
                    <span><i class="fas fa-icons"></i> Media</span>
                    <span><i class="far fa-clock"></i> Part time</span>
                    <span><i class="fas fa-money-bill-wave"></i> $25k - $32k</span>
                    <span><i class="fas fa-map-marker-alt"></i> Los Angeles, USA</span>
                </div>
                <button class="details-btn">Job Details</button>
                <i class="far fa-bookmark save-icon"></i>
            </div>
        </div>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Job</h3>
                <p>Connecting the best talent with the best companies globally.</p>
            </div>
            <div class="footer-section">
                <h3>Company</h3>
                <ul>
                    <li>About Us</li>
                    <li>Our Team</li>
                    <li>Partners</li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Job Categories</h3>
                <ul>
                    <li>Telecommunications</li>
                    <li>Hotels & Tourism</li>
                    <li>Construction</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>Privacy Policy | Terms & Conditions</p>
        </div>
    </footer>

</body>
</html>