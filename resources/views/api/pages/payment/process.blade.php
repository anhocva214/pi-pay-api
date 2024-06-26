<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Gateway</title>
    <style>
        html {
            overflow: hidden;
        }
        body {
            margin:0;
        }
        /* CSS for the loading spinner */
        .loader {
            border: 4px solid #f3f3f3;
            border-radius: 50%;
            border-top: 4px solid #3498db;
            width: 30px;
            height: 30px;
            animation: spin 2s linear infinite;
            margin: auto;
            margin-top: 50vh; /* Center vertically */
            display: none; /* Initially hidden */
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Full-width and full-height iframe */
        #contentFrame {
            width: 100vw;
            height: 100vh;
            border: none;
            display: none; /* Initially hidden */
        }
    </style>
</head>
<body>
    <div>
        <div class="loader" id="loader"></div> <!-- Loading spinner -->

        <iframe id="contentFrame"></iframe> <!-- Initially hidden -->
    </div>

    <script>
        // Function to display content in the iframe
        function displayContent(url) {
            // Show the loading spinner
            document.getElementById('loader').style.display = 'block';

            // Get the iframe element
            var iframe = document.getElementById('contentFrame');

            // Set the src attribute of the iframe to the specified URL
            iframe.src = url;

            // Add event listener for iframe load
            iframe.onload = function() {
                // Hide the loading spinner when iframe content is loaded
                document.getElementById('loader').style.display = 'none';
                // Show the iframe
                iframe.style.display = 'block';
            };
        }

        // Call the function to display content when the page loads
        window.onload = function () {
            var url = '{{$urlVnpayPayment}}';
            displayContent(url);
        };
    </script>
</body>
</html>
