## Tools for Raul

In here I'm gonna set different utilitis that I will use at my projects

### How to set up WordPress project at MCIR

1. Find project.
First thing to do is check if project its keep it inside gitlab or bitbucket.

2. Find .rmd file.
Once i find my project i'll check if exist rmd file with instructions to make it works in local or staging.

3. Add it to devilbox.
Coz best system i found at this moment to make run projects in locla is devilbox, ill add the project to devilbox flow.

4. Database.
Then i have to find project database (check in lastpass, maybe it is in ovh.phpmyadmin), see in db if it is creats table and delet it and do a search and replace of siteurl for my local url (for exemple: Http://my-projet.loc).

5. Phpmyadmin.
After customize my db, I creats new db in devilbox phpmyadmin and I do import db or I copy past the query in sql.

6. Pakcs.
If necesary ill do (composer install), or (npm i) or an other command to make work the extensions.


```markdown
Syntax highlighted code block

# Header 1
## Header 2
### Header 3

- Bulleted
- List

1. Numbered
2. List

**Bold** and _Italic_ and `Code` text

[Link](url) and ![Image](src)
```

For more details see [GitHub Flavored Markdown](https://guides.github.com/features/mastering-markdown/).

