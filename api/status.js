// JavaScript API endpoint for Vercel
export default function handler(req, res) {
  res.status(200).json({
    status: 'online',
    message: 'POS System API is available',
    note: 'This is a JavaScript API endpoint. The full PHP application requires traditional PHP hosting.',
    timestamp: new Date().toISOString(),
    github_repo: 'https://github.com/brylletorres521/POS-system'
  });
} 