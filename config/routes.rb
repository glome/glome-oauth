DoorkeeperProvider::Application.routes.draw do
  use_doorkeeper
 
  get "/oauth2" => "oauth2#grant"
  post "/oauth2" => "oauth2#grant"

end
