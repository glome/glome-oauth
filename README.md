## Instalation

    git clone git@github.com:glome/glome-oauth.git
    cd glome-oauth
    bundle install
    bundle exec rake db:setup
    
    rails server

## Code authorization 

This is the most common way to authorize with OAuth2 provider. It DOES NOT work.

### Flow

User requests authentication code from OAuth2 provider with client\_id and client\_secret. Those should be registered in OAuth2 provider, and known for client and provider.

OAuth2 service redirects user to provided redirect\_url to exchange the code and request token with client\_id, client\_secret and generated code.
In our case, user request OAuth2 service anonymously, so client\_id and client\_secret are not known to OAuth2 provider.

This could be worked around with client\_id and client\_ secret generated implicitly on provider side. However, due to doorkeeper implementation we can not override those values and we can redirect user back to original website with client\_id and client\_secret kwnown to provider only.

It means both id and secret can not be exchanged to get authorization token.

## Workaround

There is a "fake" authorization endpoint.

    /oauth2

It accepts any data given in request (like client\_id, grant\_type, etc). The main idea is to accept common, code authorization flow and implicitly change it to password grant type.

### Flow

 - User requests authorization endpoint.
 - Server creates new user, using given client\_id as username. Also new application is created for user.
 - A new form is generated to ask user if access can be granted.
 - Newly generated form is prepared to request OAuth2 provider with 'password' grant\_type
 - User grants access and requests token with password, username and new client\_id and client\_secret.
 - New token and (refresh one) are returned.


