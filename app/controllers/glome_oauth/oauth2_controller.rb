
module GlomeOauth
  class Oauth2Controller < ActionController::Base 

    def invalid_request
      render template: "oauth2/invalid_request"
    end

    def grant
      app_name = params[:client_id]
      redirect_uri = params[:redirect_uri]
      client_secret = "secret"
      client_secret = params[:client_secret]
      response_type = "token"
      #password = "ZeiChey0chatie9ohsah"
      password = ""
      #resp_type = "grant_type"
      #grant_type = "password"
      grant_type = "code"
      resp_type = "response_type"
      static_client_secret = "Zi1taishurooGhaye7BeeB8phiey2u" 
      client_secret =  static_client_secret if client_secret.nil? || client_secret.empty?

      email = app_name + "@example.com"
      user = FakeOauth2User.new(:email => email, :password=> password)
      #user = Gms::Account.new(:name => email, :password=>password)
      user.save
      u = FakeOauth2User.find_by_email(email)

      begin
        application = FakeOauth2Application.find_or_create_by(name: app_name, uid: app_name, secret: client_secret)
        application.redirect_uri = params[:redirect_uri]
        application.save
      rescue => e
        Rails.logger.error 'ERROR: ' + e.inspect
      end

      @authorization_url = request.protocol() + request.host_with_port() + "/auth/oauth/authorize"
      @client_id = application.uid
      @redirect_uri = redirect_uri
      @client_secret = application.secret
      @username = email
      @password = password
      @grant_type = grant_type
      @resp_type = resp_type

      render template: "oauth2/grant"
    end
  end
end
