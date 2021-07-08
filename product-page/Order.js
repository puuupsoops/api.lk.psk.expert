const Order = (function() {

    function recalculateTotalCount() {
        var value = 0;
        itemsPositionList.forEach(function(item) {
            value += item.position.totalAmount
        });
        return value;
    }

    function recalculateTotalWeight() {
        var value = 0;
        itemsPositionList.forEach(function(item) {
            value += item.position.totalWeight
        });
        return value.toFixed(3);
    }

    function recalculateTotalVolume() {
        var value = 0;
        itemsPositionList.forEach(function(item) {
            value += item.position.totalVolume
        });
        return value.toFixed(3);
    }

    function recalculateTotalPrice() {
        var value = 0;
        itemsPositionList.forEach(function(item) {
            value += item.position.totalCost
        });
        return value;
    }

    function findPositionByID(id) {

        let find = false;

        for (var i = 0; i < itemsPositionList.length; i++) {
            if (itemsPositionList[i].position.productId == id)
                find = itemsPositionList[i];
        }

        return find;
    }

    const protectedFields = new WeakMap();
    const itemsPositionList = new Array();

    class Order {
        constructor(id) {

            while (itemsPositionList.length > 0) {
                itemsPositionList.pop();
            }

            protectedFields.set(this, {
                uId: id,
                totalCount: 0,
                totalWeight: 0,
                totalVolume: 0,
                totalPrice: 0
            });
        }

        get Id() { return protectedFields.get(this).uId }

        get Count() { this.update(); return protectedFields.get(this).totalCount }

        get Weight() { this.update(); return protectedFields.get(this).totalWeight }

        get Valume() { this.update(); return protectedFields.get(this).totalVolume }

        get Price() { this.update(); return protectedFields.get(this).totalPrice }

        get Positions() { return itemsPositionList }

        update() {
            protectedFields.set(this, {
                uId: protectedFields.get(this).uId,
                totalCount: recalculateTotalCount(),
                totalWeight: recalculateTotalWeight(),
                totalVolume: recalculateTotalVolume(),
                totalPrice: recalculateTotalPrice(),
            });
        }

        addPosition(data) {

            var position = findPositionByID(data.productId);

            if (!position) {
                itemsPositionList.push({
                    uid: new Date().getTime(),
                    position: data
                });
            } else {

                data.OffersList.forEach(function(item) {

                    var offer = position.position.findOfferByID(item.Id);

                    console.group('Order add position');
                    console.log(position);
                    console.log(offer);
                    console.log(item);
                    console.groupEnd();

                    if (!offer) {
                        position.position.addOffer({ id: item.Id, amount: item.Amount });
                    } else {
                        offer.Update = item;
                    }
                });
            }

            this.update();
        }

        deletePosition(uid) {
            let _uid = Number.parseInt(uid);
            let _index;

            itemsPositionList.forEach(function(item, index) {

                if (Number.parseInt(item.uid) == _uid)
                    _index = index;

            });

            itemsPositionList.splice(_index, 1);
            this.update();
        }

        getPositionByUid(uid) {
            var position = false;

            for (var i = 0; i < itemsPositionList.length; i++) {
                if (itemsPositionList[i].uid == uid)
                    position = itemsPositionList[i];
            }

            if (!position) {
                return position;
            } else {
                return position;
            }

        }
    }

    return Order;

})();