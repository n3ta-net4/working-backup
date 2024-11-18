<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>AW-K9 Petshop & Grooming Station</title>
    <style>
        * {
            box-sizing: border-box;
        }       

        body, html {
            margin: 0;
            padding: 0;
            font-family: 'Helvetica', sans-serif; 
            height: 100%;
            background: url('bg.jpg') no-repeat center center fixed; 
            background-size: cover;
            color: #333;
            overflow-y: auto; 
        }

        .header {
            background-color: black;
            padding: 20px 30px; 
            display: flex;
            justify-content: space-around;
            align-items: center;
            position: fixed;
            top: 0;
            width: 100%;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);
            z-index: 100;
        }

       
        .header img {
            height: 70px; 
            margin-right: 15px;
        }

        .header h1 {
            font-size: 2em; 
            margin: 0;
            color: #fff; 
            display: flex;
            align-items: center;
        }

        .buttons {
            display: flex;
            gap: 15px;
        }

        .btn {
            background-color: #fff; 
            color: black;
            border: none;
            padding: 10px 20px; 
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: bold; 
            letter-spacing: 1px; 
            transition: background-color 0.3s, transform 0.3s;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3); 
        }

        .btn:hover {
            background-color: #ccc; 
            transform: translateY(-2px);
        }

        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: calc(100% - 80px - 60px);
            margin-top: 80px; 
            padding: 20px;
            text-align: center;
            max-height: calc(100vh - 80px - 60px); 
            overflow: auto; 
        }
        .content {
            background: rgba(255, 255, 255, 0.8);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
        }

        .content h2 {
            font-size: 1.8em;
            margin: 15px 0;
        }

        .content p {
            margin: 5px 0;
            font-size: 1em;
        }

        .additional-content {
            margin-top: 15px;
            font-size: 0.9em;
            color: #666;
        }

        @media (max-width: 600px) {
            .header h1 {
                font-size: 5vw; 
            }

            .btn {
                padding: 8px 15px; 
                font-size: 0.8em;
            }
        }

        .footer {
            background-color: black;
            color: white;
            text-align: center;
            padding: 20px;
            position: relative;
            bottom: 0;
            width: 100%;
        }

        .socials {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .social-link {
            color: white;
            text-decoration: none;
            font-size: 1em;
        }

        .social-link:hover {
            text-decoration: underline;
        }
    </style>  
</head>
<body>
    <div class="header">
        <h1>
            <img src="header-bg.png" alt="AW-K9 Logo"> 
            Petshop & Grooming Station
        </h1>
        <div class="buttons">
            <a href="login.php" class="btn">LOGIN</a>
            <a href="register.php" class="btn">REGISTER</a>
        </div>
    </div>
    <div class="container">
        <div class="content">
            <h2>Less hassle, more happiness.</h2>
            <p>Book your grooming for dogs and cats today!</p>
            <p>Reserve your spot for dog hotel services!</p>
            <p class="additional-content">Nyarks</p>
        </div>
    </div>
    <div class="footer">
    <div class="socials">
    <center>
        <a href="#" class="social-link">
            <i class="fab fa-facebook-f"></i>
        </a>
        <p>Copyright 2024. All Rights Reserved</p>
        <p>AW-K9 Petshop & Grooming Station</p>
    </center>
    </div>
</div>
</body>
</html>
