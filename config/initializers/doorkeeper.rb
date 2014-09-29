Doorkeeper.configure do
  # This block will be called to check whether the
  # resource owner is authenticated or not
  resource_owner_authenticator do
    if (params.has_key?(:username))
      username = params[:username]
    elsif (params.has_key?(:client_id))
      username = params[:client_id] + "example.com"
    end  
    u = FakeOauth2User.find_by_email(username)
  end
  #skip_authorization do
  #  true
  #end

  # If you want to restrict the access to the web interface for
  # adding oauth authorized applications you need to declare the
  # block below
  # admin_authenticator do |routes|
  #   # Put your admin authentication logic here.
  #   # If you want to use named routes from your app you need
  #   # to call them on routes object eg.
  #   # routes.new_admin_session_path
  #   Admin.find_by_id(session[:admin_id]) || redirect_to routes.new_admin_session_path
  # end

  resource_owner_from_credentials do
    if (params.has_key?(:username))
      username = params[:username]
    elsif (params.has_key?(:client_id))
      username = params[:client_id] + "example.com"
    end  
    u = FakeOauth2User.find_by_email(username)
  end

  # Access token expiration time (default 2 hours)
  # access_token_expires_in 2.hours
  access_token_expires_in 2.weeks

  # set wildcard as true
  # the case is to match urls if GET parameters (like state) are appended by the client
  wildcard_redirect_uri true

  # Issue access tokens with refresh token (disabled by default)
  use_refresh_token

  # Define access token scopes for your provider
  # For more information go to https://github.com/applicake/doorkeeper/wiki/Using-Scopes
  default_scopes  :public
  optional_scopes :write
end
