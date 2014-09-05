DoorkeeperProvider::Application.routes.draw do
  use_doorkeeper

  devise_for :users
 
  get "/oauth2" => "oauth2#grant"
  post "/oauth2" => "oauth2#grant"

  root :to => "home#index"
end
