## Glome-server notes

 Add glome\_oauth gem to Gemfile

    gem 'glome_oauth', path: "./glome_oauth"

 Mount glome\_oauth and doorkeeper (routes.rb)

    scope 'auth' do
       use_doorkeeper
    end

    mount GlomeOauth::Engine, at: "/auth", :as => 'auth'

 Install migrations

    rake glome_oauth:install:migrations

 Run migrations

    rake db:migrate 

  
## Testing

Open https://developers.google.com/oauthplayground.

On the left side, define own scope. Might be 'public' or anything.
On the right side, select OAuth2 configuration:

 OAuth flow: server-side

 OAuth endpoints: custom

 Authorization endpoint: http://glome.nemein.net/auth/oauth2

 Token endpoint: http://glome.nemein.net/auth/oauth/token

 Access token location: keep default with Bearer

 OAuth Client ID: any unique identifier

 OAuth Client secret: Zi1taishurooGhaye7BeeB8phiey2u

 (client secret might be any unique secret, if only client send this info in request. Google does not.)

 Authorize API's

 You are redirected to glome.nemein.net. Authorize.

 In step 2 (again in playground) 'Exchange authorization code for tokens'.

 Access and refresh tokens are returned.

 To test token, request http://glome.nemein.net/auth/oauth/token/info.
 Request should send token in header:

    Authorization: Bearer <token>
    
 Google's playground include this one by default.

 Returned 200 http status code - user is authorized.

 401 - unathorized.

 
