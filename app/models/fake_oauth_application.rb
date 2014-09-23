class FakeOauth2Application < ActiveRecord::Base
  self.table_name = "oauth_applications"
  attr_accessible :uid, :name, :secret, :redirect_uri
end
