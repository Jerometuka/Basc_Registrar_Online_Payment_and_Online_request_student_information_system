{
  "builds": [
    {
      "src": "router.php",
      "use": "vercel-php@0.6.2"
    }
  ],
  "rewrites": [
    {
      "source": "/(.*\\.php)",
      "destination": "/router.php?page=$1"
    },
    {
      "source": "/",
      "destination": "/router.php?page=signin.php"
    }
  ]
}
