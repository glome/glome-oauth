class FakeOauth2User < ActiveRecord::Base
  attr_accessible :email, :username, :password
end
