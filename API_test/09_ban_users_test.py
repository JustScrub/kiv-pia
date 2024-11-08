import requests
from common import get_id, HOST

def test_low_rights():
    response = requests.put(f'{HOST}/api.php?service=ban_users', 
                             headers={'Authorization': get_id("test")},
                             json=["toban0"])
    assert response.status_code == 401, response.text

def test_no_param():
    response = requests.put(f'{HOST}/api.php?service=ban_users', 
                             headers={'Authorization': get_id("admin")})
    assert response.status_code == 400, response.text
    j = response.json()
    assert "Missing request body" in j["message"]

def test_bad_param():
    response = requests.put(f'{HOST}/api.php?service=ban_users', 
                             headers={'Authorization': get_id("admin")}, 
                             json={"body": "bad", "should_be":"list of logins or emails"})
    assert response.status_code == 400, response.text
    j = response.json()
    assert  "Body must be an array of strings" in j["message"]

def test_ok():
    response = requests.put(f'{HOST}/api.php?service=ban_users', 
                             headers={'Authorization': get_id("admin")},
                             json=["superadmin","toban0", "toban1@test.com", "toban2", "banned@test.com", "noexist"])
    assert response.status_code == 200, response.text
    j = response.json()
    assert isinstance(j, list)
    for b in ["toban0", "toban1@test.com", "toban2", "banned@test.com"]:
        assert b in j
    for b in ["superadmin", "noexist"]:
        assert b not in j

if __name__ == "__main__":
    test_low_rights()
    test_no_param()
    test_bad_param()
    test_ok()
    print("All tests passed!")