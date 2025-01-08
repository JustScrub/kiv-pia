import requests, os
from common import get_id, is_subdict, HOST
# correct: call add_article endpoint with correct parameters, then upload_article endpoint where body is the pdf article
data = ""

def upload_arr(usr):
    global data
    if not data:
        with open("test-article.pdf", "rb") as f:
            data = f.read()
    return requests.post(f'{HOST}/api.php?service=upload_article', 
                                 headers={'Authorization': get_id(usr), 'Content-Type': 'application/pdf'},
                                 data=data)

def test_add_no_param():
    response = requests.get(f'{HOST}/api.php?service=add_article', 
                            headers={'Authorization': get_id("adar")})
    assert response.status_code == 400
    assert "Missing request body" in response.json()["message"]

def test_add_bad_param():
    response = requests.post(f'{HOST}/api.php?service=add_article', 
                             headers={'Authorization': get_id("adar")},
                             json={"abstract": "test"})
    assert response.status_code == 400
    assert "Missing body parameter" in response.json()["message"]

def test_add_new():
    response = requests.post(f'{HOST}/api.php?service=add_article', 
                             headers={'Authorization': get_id("adar")},
                             json={"title": "Add article test", "descr": "Test", "key-words": "Test"})
    assert response.status_code == 200
    # upload the article to show it in the list of user articles
    response = upload_arr("adar")
    response = requests.get(f'{HOST}/api.php?service=get_user_articles&login=adar', 
                            headers={'Authorization': get_id("adar")})
    assert response.status_code == 200
    j = response.json()
    assert isinstance(j, list), response.text
    assert len(j) == 1, response.text
    assert is_subdict({
        "title": "Add article test",
        "descr": "Test",
        "key-words": "Test",
        "author_id": 9,
        "approved": "pending"
    },j[0]), response.text
    id = j[0]["id"]
    # delete the article
    response = requests.delete(f'{HOST}/api.php?service=delete_article&id={id}', 
                            headers={'Authorization': get_id("adar")})
    
def test_add_after_add():
    response = requests.post(f'{HOST}/api.php?service=add_article', 
                             headers={'Authorization': get_id("adar")},
                             json={"title": "Add article test", "descr": "Test", "key-words": "Test"})
    response = requests.post(f'{HOST}/api.php?service=add_article', 
                             headers={'Authorization': get_id("adar")},
                             json={"title": "Ovewritten", "descr": "OW", "key-words": "OW"})
    assert response.status_code == 200
    # upload the article to show it in the list of user articles
    response = upload_arr("adar")
    response = requests.get(f'{HOST}/api.php?service=get_user_articles&login=adar', 
                            headers={'Authorization': get_id("adar")})
    j = response.json()
    assert len(j) == 1, response.text
    assert is_subdict({
        "title": "Ovewritten",
        "descr": "OW",
        "key-words": "OW",
        "author_id": 9,
        "approved": "pending"
    },j[0]), response.text
    id = j[0]["id"]
    # delete the article
    response = requests.delete(f'{HOST}/api.php?service=delete_article&id={id}', 
                            headers={'Authorization': get_id("adar")})

def test_upload_no_param():
    response = requests.get(f'{HOST}/api.php?service=upload_article', 
                            headers={'Authorization': get_id("adar")})
    assert response.status_code == 400, response.text
    assert "Missing request body" in response.json()["message"]

def test_upload_no_add():
    response = upload_arr("adar")
    assert response.status_code == 404, response.text
    assert  "No article data found. Add article information first" in response.json()["message"]

def test_upload_ok():
    ardir_content_before = os.listdir("../web/Articles")
    response = requests.post(f'{HOST}/api.php?service=add_article', 
                             headers={'Authorization': get_id("adar")},
                             json={"title": "Add article test", "descr": "Test", "key-words": "Test"})
    response = upload_arr("adar")
    assert response.status_code == 200, response.text
    ardir_content = os.listdir("../web/Articles")
    assert len(ardir_content) == len(ardir_content_before) + 1, (ardir_content, ardir_content_before, response.text)
    fname = [f for f in ardir_content if f not in ardir_content_before][0]
    with open("../web/Articles/" + fname, "rb") as f:
        assert f.read() == data
    response = requests.get(f'{HOST}/api.php?service=get_user_articles&login=adar', 
                            headers={'Authorization': get_id("adar")})
    j = response.json()
    assert len(j) == 1, response.text
    id = j[0]["id"]
    # show article
    response = requests.get(f'{HOST}/api.php?service=show_article&id={id}', 
                            headers={'Authorization': get_id("adar")})
    assert response.status_code == 200, response.text
    assert response.content == data

    # delete the article
    response = requests.delete(f'{HOST}/api.php?service=delete_article&id={id}', 
                            headers={'Authorization': get_id("adar")})

if __name__ == "__main__":
    test_add_no_param()
    test_add_bad_param()
    test_add_new()
    test_add_after_add()
    test_upload_no_param()
    test_upload_no_add()
    test_upload_ok()
    print("All tests passed!")