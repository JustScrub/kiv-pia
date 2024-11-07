
import requests, pytest
from common import get_id, dict_in_list, article_id_in_list, HOST

acc_articles = [
    {"id": 2, "auth": 3},
    {"id": 5, "auth": 1},
    ]


other_articles = [1,3,4,6,7,9]

def test_ok_positive():
    response = requests.get(f'{HOST}/api.php?service=get_articles', 
                            headers={'Authorization': get_id()})
    assert response.status_code == 200
    j = response.json()
    assert isinstance(j, list)
    for a in acc_articles:
        assert dict_in_list(j, {
            "id": a["id"],
            "author_id": a["auth"],
            "approved": "yes",
            "title": f"Testovaci clanek {a['id']}",
            "descr":  'Testovaci clanek pro testovani',
            "key-words": 'test, testovani',
        }), j

def test_ok_negative():
    response = requests.get(f'{HOST}/api.php?service=get_articles', 
                            headers={'Authorization': get_id()})
    assert response.status_code == 200
    j = response.json()
    assert isinstance(j, list)
    for a in other_articles:
        assert not article_id_in_list(j, a)

if __name__ == "__main__":
    test_ok_positive()
    test_ok_negative()
    print("All tests passed!")