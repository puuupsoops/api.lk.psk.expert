class ItemOffer {
    constructor(offer, product) {

        this._id = offer.id;
        this._price = Number.parseFloat(product.getOfferById(offer.id).Price);
        this._name = product.getOfferById(offer.id).Characteristic;
        this._complictation = false;
        this._amount = Number.parseInt(offer.amount);
        this._cost = (Number.parseFloat(product.getOfferById(offer.id).Price) * Number.parseFloat(offer.amount));
    }

    get Id() { return this._id }

    get Price() { return this._price }

    get Name() { return this._name }

    get Complictation() { return this._complictation }

    get Amount() { return this._amount }

    get Cost() { return Number.parseFloat(this._cost).toFixed(2) }

    set Update(data) {
        this._amount += Number.parseInt(data.Amount);
        this._cost = Number.parseFloat(this._price * this._amount).toFixed(2);
    }

    increase() { this._amount++ }

    decrease() {
        (this._amount == 0) ? this._amount = 0: this._amount--
    }
}