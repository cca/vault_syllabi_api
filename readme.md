# VAULT API for Syllabi

A wrapper around the EQUELLA (software that runs VAULT) API for retrieving syllabi. Allows someone to interact with the files and metadata related to the collection in VAULT without knowing the internal workings. Live demo at http://libraries.cca.edu/syllabi-api/

## Request Parameters

By default, the API just executes a search of VAULT's Syllabus Collection with all the default settings and returns a list of 10 items serialized in JSON. Here are some parameters one can alter to obtain different results:

### Queries

- **semester**: limit the semester results are from, semesters are of form "(Spring|Fall|Summer) YYYY" e.g. `Spring 2015`
- **section**: limit the section results are from, sections are of form `ARCHT-101-01` where the first five capital letters are a department code
- **q**: free text query to execute, e.g. "visual arts"

We can add the capability to query additional metadata fields, such as course title or faculty names, if need be.

### Response Limits/Sorting

- **length**: default `10`, maximum number of results to return
- **start**: default `0`, number of the first search result to return (useful for paging a long list of results)
- **order**: ordering principle of the results list, defaults to VAULT's internal relevance ranking but can also be set to `modified` (date last modified) or `name` (alphabetical by name)
- **reverse**: default `false`, whether results should be listed in reverse, set to `true` to override

There is also a **debug** parameter which, when set to any value, causes the app to return the raw EQUELLA API response instead of its modified response. This is useful for development purposes but probably not for most API clients.

## Notes

Users will still need to sign into VAULT to access the syllabi file itself. VAULT is not on CAS yet, either, though we have plans to move to it soon.

Even for a semester-section pair which logically should be unique, multiple syllabi may be returned because faculty may have accidentally upload their syllabi to VAULT multiple times. The best way to obtain a particular syllabus is to set `order` to `modified` and to take the first entry in the response's `results` array (see the **Sample Response** below). It's also possible that no syllabi have been contributed for a course. We attempt to collect them all but certain sections do not have them (typically graduate studios, independent studies) and sometimes faculty do not upload or upload very late in the semester.

## Sample Response

See also [response.json](blob/master/response.json) and the [example.html](blob/master/example.html) in this folder, which demonstrates how a JavaScript client uses the API. When I perform a `GET http://libraries.cca.edu/syllabi-api/?semester=Spring+2015&section=WRLIT-100-01` HTTP request I see the following:

```js
{
    "vault_api_url": "https://vault.cca.edu/api/search?info=metadata%2Cbasic%2Cattachment&collections=9ec74523-e018-4e01-ab4e-be4dd06cdd68&where=%2Fxml%2Flocal%2FcourseInfo%2Fsemester%20%3D%20%27Spring%202015%27%20AND%20%2Fxml%2Flocal%2FcourseInfo%2Fsection%20%3D%20%27WRLIT-100-01%27",
    "results": [
        {
            "name": "Spring 2015 | WRLIT-100 | Writing 1: Language Dynamics",
            "link": "https://vault.cca.edu/items/d15b3d2a-3037-4f70-96ce-62f6619449dc/1/",
            "attachments": [
                {
                    "type": "file",
                    "uuid": "21376771-0ab3-4db5-961e-2a3bf0a8a7f4",
                    "description": "Mitsanas Syllabus Spring 2015 Wtg 1.docx",
                    "viewer": "",
                    "preview": false,
                    "restricted": false,
                    "filename": "Spring 2015 Wtg 1.docx",
                    "size": 136367,
                    "conversion": false,
                    "links": {
                        "view": "https://vault.cca.edu/items/d15b3d2a-3037-4f70-96ce-62f6619449dc/1/?attachment.uuid=21376771-0ab3-4db5-961e-2a3bf0a8a7f4",
                        "thumbnail": "https://vault.cca.edu/thumbs/d15b3d2a-3037-4f70-96ce-62f6619449dc/1/21376771-0ab3-4db5-961e-2a3bf0a8a7f4"
                    }
                }
            ],
            "semester": "Spring 2015",
            "department": "WRLIT",
            "course": "Writing 1: Language Dynamics",
            "faculty": "Eugenia Mitsanas",
            "section": "WRLIT-100-01",
            "courseName": "WRLIT-100",
            "facultyID": "emitsanas"
        }
    ]
}

```

All the truly useful information in this response is self-explanatory. Much of what's inside the "attachments" array is EQUELLA jargon but note that `<attachment>.links.view` is a link to download the syllabus file itself and `<attachment>.links.thumbnail` is a thumbnail image generated from the file.

## Development

Uses [Composer](https://getcomposer.org/) to manage the [Guzzle](https://guzzle3.readthedocs.org/http-client/client.html) HTTP library dependency. Run `composer install` to get set up. We have to stick with Guzzle 3.x due to the outdated PHP version on the libraries' web server.

The application relies upon a client credentials grant OAUTH application in EQUELLA and a `SYLLABI_API_TOKEN` environment variable that is set to the OAUTH application's token. You can do this with a line like `SetEnv SYLLABI_API_TOKEN "abcd1234-1234-aabb…"` in an .htaccess, for instance.

## License

[Apache 2.0](https://www.apache.org/licenses/LICENSE-2.0)
