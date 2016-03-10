
all:
	open "http://localhost:4004/"
	bundle exec jekyll serve --port 4004

drafts:
	bundle exec jekyll serve --drafts
