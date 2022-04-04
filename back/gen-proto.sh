rm -rf app/Protos &&\
protoc --proto_path=protos --php_out=protos protos/masterstorge.proto &&\
cp -r protos/App/Protos app