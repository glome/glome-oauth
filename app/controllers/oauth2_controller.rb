
class Oauth2Controller < ActionController::Base 

  def invalid_request
    render template: "oauth2/invalid_request"
  end

  def grant
    app_name = params[:client_id]
    redirect_uri = params[:redirect_uri]
    client_secret = "secret"
    response_type = "token"
    password = "ZeiChey0chatie9ohsah"
    grant_type = "password"

    email = app_name + "@example.com"
    user = FakeOauth2User.new(:email => email, :password=> password)
    #user = Gms::Account.new(:name => email, :password=>password)
    user.save
    u = FakeOauth2User.find_by_email(email)
    application = Doorkeeper::Application.new(name: app_name, redirect_uri: params[:redirect_uri])
    r = application.save

    @authorization_url = request.protocol() + request.host_with_port() + "/oauth/token"
    @client_id = application.uid
    @redirect_uri = redirect_uri
    @client_secret = application.secret
    @username = email
    @password = password
    @grant_type = grant_type

    render template: "oauth2/grant"
  end

end
