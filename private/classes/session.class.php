<?php

/* 
* session.class.php
* Manages session state for StagePlotter.dev
* 
* Stores user identity and role from user_usr after login.
* Provides role-aware access checks via numeric hierarchy
* 
* Roles (low -> high): public (unautheticated), member, admin
* 'public' is not stored in the DB - it means is_logged_in() === false.
*
*@author ALyssa Companioni
*
*/


class Session
{

  private int $user_id;
  public string $username = '';
  public string $first_name = '';
  private string $role = '';
  private int $last_login = 0;

  // Session expires after 24 hours of inactivity 
  public const MAX_LOGIN_AGE = 60 * 60 * 24;

  // Role hierarchy 
  private const ROLE_HIERARCHY = [
    'member' => 1,
    'admin'  => 2,
  ];

  // Constructor
  public function __contruct() {
    if(session_status() == PHP_SESSION_NONE) {
      session_start();
    }
  }
}


