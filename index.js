/**
 * DocDigest Root Entry Point
 * This file acts as a bridge for hosting platforms (like Hostinger or Vercel)
 * that expect an index.js file in the root directory.
 */

// Import the actual server logic from the backend folder
require('./backend/server.js');
