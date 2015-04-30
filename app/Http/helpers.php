<?php

// Helper functions
function ws_url()
{
  return "ws://".Request::server("SERVER_NAME").":".env("WS_PORT");
}