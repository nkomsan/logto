<html>

<head>
  <title>Logto PHP Integration</title>
</head>

<body>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; max-width: 800px; margin: 0 auto; padding: 20px; background-color: #f4f7f6; }
    h2 { color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-top: 30px; }
    pre { background-color: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 5px; overflow-x: auto; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
    a { color: #3498db; text-decoration: none; font-weight: bold; transition: color 0.3s ease; }
    a:hover { color: #2980b9; text-decoration: underline; }
    .nav-links { background: white; padding: 15px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; display: flex; gap: 20px; align-items: center; }
    .mfa-link { background-color: #e67e22; color: white !important; padding: 8px 15px; border-radius: 4px; transition: background 0.3s ease; }
    .mfa-link:hover { background-color: #d35400; text-decoration: none; }
    .btn-signout { color: #e74c3c !important; }
    .btn-signout:hover { color: #c0392b !important; }
  </style>

  <?php
  require __DIR__ . '/vendor/autoload.php';

  use Logto\Sdk\LogtoClient;
  use Logto\Sdk\LogtoConfig;
  use Logto\Sdk\Constants\UserScope;
  use Logto\Sdk\InteractionMode;
  use Logto\Sdk\Models\DirectSignInOptions;
  use Logto\Sdk\Constants\DirectSignInMethod;
  use Logto\Sdk\Constants\FirstScreen;
  use Logto\Sdk\Constants\AuthenticationIdentifier;
  
  $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
  $dotenv->load();

  // SSL Verification bypass (Development only)
  $contextOptions = [
    'ssl' => [
      'verify_peer' => false,
      'verify_peer_name' => false,
    ],
  ];
  stream_context_set_default($contextOptions);

  $client = new LogtoClient(
    new LogtoConfig(
      $_ENV['LOGTO_ENDPOINT'],
      $_ENV['LOGTO_APP_ID'],
      $_ENV['LOGTO_APP_SECRET'],
      [UserScope::email, UserScope::organizations, UserScope::organizationRoles]
    )
  );

  switch (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) {
    case '/':
    case null:
      if (!$client->isAuthenticated()) {
        echo '<h3>Sign In Options:</h3>';
        echo '<ul>';
        echo '<li><a href="/sign-in">Normal Sign In</a></li>';
        echo '<li><a href="/sign-in/sign-up">Sign In (Sign Up First)</a></li>';
        echo '<li><a href="/sign-in/social">Sign In with GitHub</a></li>';
        echo '<li><a href="/sign-in/email-and-username">Sign In with Email and Username</a></li>';
        echo '</ul>';
        break;
      }

      $accountCenterUrl = rtrim($_ENV['LOGTO_ENDPOINT'], '/') . '/account/security';

      echo '<div class="nav-links">';
      echo '<a href="/">Home</a>';
      echo '<a href="organizations">Organizations</a>';
      echo '<a href="' . $accountCenterUrl . '" target="_blank" class="mfa-link">Manage MFA (Select/Cancel)</a>';
      echo '<a href="sign-out" class="btn-signout">Sign out</a>';
      echo '</div>';

      echo '<h2>Userinfo</h2>';
      echo '<pre>';
      echo var_export($client->fetchUserInfo(), true);
      echo '</pre>';
      echo '<h2>ID token claims</h2>';
      echo '<pre>';
      echo var_export($client->getIdTokenClaims(), true);
      echo '</pre><br>';
      break;
    
    case '/organizations':
      if (!$client->isAuthenticated()) {
        header('Location: /sign-in');
        exit();
      }

      echo '<div class="nav-links">';
      echo '<a href="/">Home</a>';
      echo '<a href="sign-out" class="btn-signout">Sign out</a>';
      echo '</div>';

      $claims = $client->getIdTokenClaims();
      $organizations = isset($claims->organizations) ? $claims->organizations : [];
      $orgId = isset($_GET['org_id']) ? $_GET['org_id'] : (count($organizations) > 0 ? $organizations[0] : null);

      echo '<h2>Organizations</h2>';
      if (empty($organizations)) {
        echo '<p>You are not a member of any organization.</p>';
      } else {
        echo '<ul>';
        foreach ($organizations as $id) {
          $activeStyle = $id === $orgId ? 'font-weight: bold; text-decoration: underline;' : '';
          echo '<li><a href="/organizations?org_id=' . urlencode($id) . '" style="' . $activeStyle . '">' . htmlspecialchars($id) . '</a></li>';
        }
        echo '</ul>';
      }
      
      if ($orgId) {
        echo '<h2>Organization token claims for: ' . htmlspecialchars($orgId) . '</h2>';
        echo '<pre>';
        try {
          echo var_export($client->getOrganizationTokenClaims($orgId), true);
        } catch (\Exception $e) {
          echo 'Error fetching organization token: ' . htmlspecialchars($e->getMessage());
        }
        echo '</pre>';
      }
      break;

    case '/sign-in':
      header('Location: ' . $client->signIn($_ENV['LOGTO_REDIRECT_URI']));
      exit();

    case '/sign-in/sign-up':
      header('Location: ' . $client->signIn(
        $_ENV['LOGTO_REDIRECT_URI'],
        interactionMode: InteractionMode::signUp
      ));
      exit();

    case '/sign-in/social':
      header('Location: ' . $client->signIn(
        $_ENV['LOGTO_REDIRECT_URI'],
        directSignIn: new DirectSignInOptions(
          method: DirectSignInMethod::social,
          target: 'github'
        )
      ));
      exit();

    case '/sign-in/email-and-username':
      header('Location: ' . $client->signIn(
        $_ENV['LOGTO_REDIRECT_URI'],
        firstScreen: FirstScreen::signIn,
        identifiers: [AuthenticationIdentifier::email, AuthenticationIdentifier::username]
      ));
      exit();

    case '/sign-in-callback':
      $client->handleSignInCallback();
      header('Location: /');
      exit();

    case '/sign-out':
      $to = $client->signOut($_ENV['LOGTO_POST_LOGOUT_REDIRECT_URI']);
      header("Location: $to");
      exit();

    default:
      echo '404 - Not Found';
      break;
  }
  ?>

</body>

</html>