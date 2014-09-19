$:.push File.expand_path("../lib", __FILE__)

# Describe your gem and declare its dependencies:
Gem::Specification.new do |s|
  s.name        = "glome_oauth"
  s.version     = "0.0.1"
  s.authors     = ["Piotr Pokora"]
  s.email       = ["piotr.pokora@nemein.com"]
  s.homepage    = ""
  s.summary     = ""
  s.description = ""

  s.files = Dir["{app,config,db,lib}/**/*", "MIT-LICENSE", "Rakefile", "README.rdoc"]
  s.test_files = Dir["test/**/*"]
  
  s.add_dependency "rails", "~> 4.0.3"
  s.add_dependency "doorkeeper", "1.4.0"
  s.add_development_dependency "sqlite3"
end

