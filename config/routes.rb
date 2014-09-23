GlomeOauth::Engine.routes.draw do
  scope 'auth' do
    use_doorkeeper
  end  
 
  get "/oauth2" => "oauth2#grant"
  post "/oauth2" => "oauth2#grant"

end
