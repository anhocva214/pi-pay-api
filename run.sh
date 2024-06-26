#!/bin/bash
docker image rm pipay_api:latest
docker build -t pipay_api:latest .
docker rm -f pipay_api
docker run -it -d -p 8000:8181 --name pipay_api pipay_api:latest
# docker run -it -d --name pipay_api --network apps -e LETSENCRYPT_EMAIL="anhocva@gmail.com" pipay_api:latest