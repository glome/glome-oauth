class CreateFakeOauth2Users < ActiveRecord::Migration
  def change
    create_table :fake_oauth2_users do |t|
      t.string :email
      t.string :password
      t.string :username

      t.timestamps
    end
  end
end
