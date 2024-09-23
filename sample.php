<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .container {
            display: flex;
            flex-direction: column;
            height: 100vh;
        }
        .header {
            background: #4CAF50;
            color: white;
            padding: 10px;
            text-align: center;
        }
        .sidebar {
            width: 200px;
            background: #f4f4f4;
            padding: 15px;
        }
        .main-content {
            flex-grow: 1;
            padding: 15px;
        }
        ul {
            list-style: none;
            padding: 0;
        }
        li {
            margin: 10px 0;
        }
        a {
            text-decoration: none;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Dashboard Header</h1>
        </header>
        <div class="sidebar">
            <ul>
                <li><a href="#" class="menu-item" data-page="dashboard">Dashboard</a></li>
                <li><a href="#" class="menu-item" data-page="bus">Bus</a></li>
                <li><a href="#" class="menu-item" data-page="route">Route</a></li>
                <li><a href="#" class="menu-item" data-page="customer">Customer</a></li>
                <li><a href="#" class="menu-item" data-page="bookings">Bookings</a></li>
            </ul>
        </div>
        <div class="main-content">
            <div id="main-content">
            </div>
        </div>
    </div>
    <script>
        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('click', function(event) {
                event.preventDefault();
                const page = this.getAttribute('data-page');

                // Simulate fetching content
                let content = '';
                switch (page) {
                    case 'dashboard':
                        content = '<h2>Dashboard Content</h2><p>This is the dashboard content.</p>';
                        break;
                    case 'bus':
                        content = '<h2>Bus Content</h2><p>This is the bus content.</p>';
                        break;
                    case 'route':
                        content = '<h2>Route Content</h2><p>This is the route content.</p>';
                        break;
                    case 'customer':
                        content = '<h2>Customer Content</h2><p>This is the customer content.</p>';
                        break;
                    case 'bookings':
                        content = '<h2>Bookings Content</h2><p>This is the bookings content.</p>';
                        break;
                    default:
                        content = '<h2>Welcome to the Dashboard</h2><p>Select a menu item to view content.</p>';
                }

                document.getElementById('main-content').innerHTML = content;
            });
        });
    </script>
</body>
</html>